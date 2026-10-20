<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentoController extends Controller
{
    /**
     * The 3 official hospital templates.
     *
     * @var array<string, array{archivo: string, titulo: string, descripcion: string}>
     */
    protected array $plantillas = [
        'historia-clinica' => [
            'archivo' => 'Historia_Clinica.docx',
            'titulo' => 'Historia Clínica Pediátrica',
            'descripcion' => 'Historia Clínica General',
        ],
        'indicaciones' => [
            'archivo' => 'Indicaciones.docx',
            'titulo' => 'Hoja de Indicaciones Médicas',
            'descripcion' => 'Indicaciones y Tratamiento',
        ],
        'admision' => [
            'archivo' => 'Admision.docx',
            'titulo' => 'Admisión Pediátrica',
            'descripcion' => 'Hoja de Admisión',
        ],
    ];

    /**
     * Obtiene la lista de discos del sistema con su estado y ruta a Pediatria.
     *
     * @return array<int, array{disco: string, raiz: string, ruta_pediatria: string, escribible: bool, es_predeterminado: bool}>
     */
    public function obtenerDiscosDisponibles(): array
    {
        $discos = [];
        $letras = ['D', 'C', 'E', 'F'];
        $discoPredeterminado = $this->obtenerDiscoPredeterminado();

        foreach ($letras as $letra) {
            $raiz = "{$letra}:/";
            if (is_dir($raiz)) {
                $escribible = @is_writable($raiz);
                $rutaPediatria = "{$letra}:\\Pediatria";

                if ($letra === 'C' && ! $escribible && ! is_dir('C:/Pediatria')) {
                    $rutaPediatria = str_replace('/', '\\', base_path('Pediatria'));
                }

                $discos[] = [
                    'disco' => $letra,
                    'raiz' => "{$letra}:\\",
                    'ruta_pediatria' => $rutaPediatria,
                    'escribible' => $escribible || is_dir("{$letra}:/Pediatria"),
                    'es_predeterminado' => ($letra === $discoPredeterminado),
                ];
            }
        }

        if (empty($discos)) {
            $discoBase = strtoupper(substr(base_path(), 0, 1));
            $discos[] = [
                'disco' => $discoBase,
                'raiz' => "{$discoBase}:\\",
                'ruta_pediatria' => str_replace('/', '\\', base_path('Pediatria')),
                'escribible' => true,
                'es_predeterminado' => true,
            ];
        }

        return $discos;
    }

    /**
     * Determina el disco predeterminado (D: si existe y es escribible, o C: o el configurado).
     */
    public function obtenerDiscoPredeterminado(): string
    {
        $configDisco = env('PEDIATRIA_DISCO');
        if ($configDisco && is_dir(strtoupper($configDisco).':/')) {
            return strtoupper($configDisco);
        }

        if (is_dir('D:/') && @is_writable('D:/')) {
            return 'D';
        }

        $letraProyecto = strtoupper(substr(base_path(), 0, 1));

        return $letraProyecto ?: 'C';
    }

    /**
     * Resuelve la ruta base para la carpeta Pediatria según el disco elegido.
     */
    public function getRutaBasePediatria(?string $disco = null): string
    {
        $discoLetra = $disco ? strtoupper(trim($disco, ':\\/ ')) : $this->obtenerDiscoPredeterminado();

        if (! in_array($discoLetra, ['C', 'D', 'E', 'F']) || ! is_dir("{$discoLetra}:/")) {
            $discoLetra = $this->obtenerDiscoPredeterminado();
        }

        if ($discoLetra === 'C' && ! @is_writable('C:/') && ! is_dir('C:/Pediatria')) {
            return base_path('Pediatria');
        }

        return "{$discoLetra}:/Pediatria";
    }

    /**
     * Extrae la letra del disco de una ruta.
     */
    public function extraerLetraDisco(string $ruta): string
    {
        if (preg_match('/^([A-Za-z]):/', $ruta, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'C';
    }

    /**
     * Register a new patient and automatically generate their 3 documents
     * inside Pediatria/{fecha}/{nombre_paciente}/
     */
    public function ingresarPaciente(Request $request): JsonResponse
    {
        $nombreInput = trim((string) $request->input('nombre', ''));

        if (empty($nombreInput)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, ingrese el nombre del paciente.',
            ], 422);
        }

        $pacienteDir = $this->sanitizarCarpeta($nombreInput);
        if (empty($pacienteDir)) {
            $pacienteDir = 'Paciente_'.now()->format('His');
        }

        $discoInput = $request->input('disco');
        $rutaBase = $this->getRutaBasePediatria($discoInput);
        $letraDisco = $this->extraerLetraDisco($rutaBase);

        $fechaHoy = now()->format('Y-m-d');
        $carpetaDestino = "{$rutaBase}/{$fechaHoy}/{$pacienteDir}";

        // If directory already exists for this patient today, append timestamp
        if (File::exists($carpetaDestino)) {
            $carpetaDestino .= '_'.now()->format('His');
            $pacienteDir = basename($carpetaDestino);
        }

        File::ensureDirectoryExists($carpetaDestino, 0755, true);

        $archivosGenerados = [];
        $plantillasFaltantes = [];

        foreach ($this->plantillas as $key => $info) {
            $rutaOrigen = base_path('Plantillas/'.$info['archivo']);

            if (File::exists($rutaOrigen)) {
                $nombreArchivo = $info['archivo'];
                $rutaDestinoArchivo = "{$carpetaDestino}/{$nombreArchivo}";

                copy($rutaOrigen, $rutaDestinoArchivo);

                $bytes = filesize($rutaDestinoArchivo);
                $tamano = $bytes >= 1048576
                    ? number_format($bytes / 1048576, 2).' MB'
                    : number_format($bytes / 1024, 0).' KB';

                $archivosGenerados[] = [
                    'tipo' => $key,
                    'titulo' => $info['titulo'],
                    'nombre' => $nombreArchivo,
                    'tamano' => $tamano,
                    'url' => route('documentos.descargar-archivo', [
                        'fecha' => $fechaHoy,
                        'paciente' => $pacienteDir,
                        'archivo' => $nombreArchivo,
                        'disco' => $letraDisco,
                    ]),
                ];
            } else {
                $plantillasFaltantes[] = $info['archivo'];
            }
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Paciente ingresado con éxito.',
            'paciente' => $nombreInput,
            'paciente_carpeta' => $pacienteDir,
            'fecha' => $fechaHoy,
            'disco' => $letraDisco,
            'ruta_completa' => str_replace('/', '\\', $carpetaDestino),
            'ruta_relativa' => "{$letraDisco}:\\Pediatria\\{$fechaHoy}\\{$pacienteDir}",
            'archivos' => $archivosGenerados,
            'plantillas_faltantes' => $plantillasFaltantes,
            'zip_url' => route('documentos.descargar-zip', [
                'fecha' => $fechaHoy,
                'paciente' => $pacienteDir,
                'disco' => $letraDisco,
            ]),
        ]);
    }

    /**
     * Get patient history with date, search and disk filters.
     */
    public function historial(Request $request): JsonResponse
    {
        $filtroFecha = trim((string) $request->input('fecha', ''));
        $filtroBuscar = mb_strtolower(trim((string) $request->input('buscar', '')));
        $filtroDisco = $request->input('disco') ? strtoupper(trim((string) $request->input('disco'), ':\\/ ')) : null;

        $rutasPediatria = [];
        if ($filtroDisco) {
            $rutasPediatria[] = $this->getRutaBasePediatria($filtroDisco);
        } else {
            $rutaPredeterminada = $this->getRutaBasePediatria();
            $rutasPediatria[] = $rutaPredeterminada;

            $rutaProyecto = base_path('Pediatria');
            if (! in_array($rutaProyecto, $rutasPediatria) && File::exists($rutaProyecto)) {
                $rutasPediatria[] = $rutaProyecto;
            }

            if (File::exists('D:/Pediatria') && ! in_array('D:/Pediatria', $rutasPediatria)) {
                $rutasPediatria[] = 'D:/Pediatria';
            }
        }

        $fechasDisponibles = [];
        $pacientes = [];

        foreach ($rutasPediatria as $rutaPediatria) {
            if (! File::exists($rutaPediatria)) {
                continue;
            }

            $discoLetra = $this->extraerLetraDisco($rutaPediatria);
            $carpetasFechas = File::directories($rutaPediatria);

            foreach ($carpetasFechas as $carpetaFecha) {
                $fecha = basename($carpetaFecha);

                // FILTRO ESTRICTO: Solo carpetas con formato de fecha YYYY-MM-DD son carpetas de pacientes.
                // Ignora carpetas como MaterialDocente, Plantillas o cualquier otra carpeta no clínica.
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    continue;
                }

                $fechasDisponibles[] = $fecha;

                if (! empty($filtroFecha) && $fecha !== $filtroFecha) {
                    continue;
                }

                $carpetasPacientes = File::directories($carpetaFecha);

                foreach ($carpetasPacientes as $carpetaPaciente) {
                    $pacienteNombreCarpeta = basename($carpetaPaciente);
                    $pacienteNombreLegible = str_replace(['_', '-'], ' ', $pacienteNombreCarpeta);

                    if (! empty($filtroBuscar)) {
                        $coincide = str_contains(mb_strtolower($pacienteNombreCarpeta), $filtroBuscar)
                            || str_contains(mb_strtolower($pacienteNombreLegible), $filtroBuscar);

                        if (! $coincide) {
                            continue;
                        }
                    }

                    $files = File::files($carpetaPaciente);
                    $archivos = [];

                    foreach ($files as $file) {
                        $bytes = $file->getSize();
                        $tamano = $bytes >= 1048576
                            ? number_format($bytes / 1048576, 2).' MB'
                            : number_format($bytes / 1024, 0).' KB';

                        $archivos[] = [
                            'nombre' => $file->getFilename(),
                            'tamano' => $tamano,
                            'hora' => date('H:i', $file->getMTime()),
                            'url' => route('documentos.descargar-archivo', [
                                'fecha' => $fecha,
                                'paciente' => $pacienteNombreCarpeta,
                                'archivo' => $file->getFilename(),
                                'disco' => $discoLetra,
                            ]),
                        ];
                    }

                    $mtime = filemtime($carpetaPaciente);

                    $pacientes[] = [
                        'nombre' => $pacienteNombreLegible,
                        'carpeta' => $pacienteNombreCarpeta,
                        'fecha' => $fecha,
                        'fecha_formateada' => date('d/m/Y', strtotime($fecha)),
                        'disco' => $discoLetra,
                        'ruta_completa' => str_replace('/', '\\', $carpetaPaciente),
                        'ruta_relativa' => "{$discoLetra}:\\Pediatria\\{$fecha}\\{$pacienteNombreCarpeta}",
                        'total_archivos' => count($archivos),
                        'archivos' => $archivos,
                        'zip_url' => route('documentos.descargar-zip', [
                            'fecha' => $fecha,
                            'paciente' => $pacienteNombreCarpeta,
                            'disco' => $discoLetra,
                        ]),
                        'hora' => date('H:i', $mtime),
                        '_mtime' => $mtime,
                    ];
                }
            }
        }

        // Sort patients by directory modification time descending
        usort($pacientes, fn ($a, $b) => $b['_mtime'] <=> $a['_mtime']);

        // Remove temporary sort key
        foreach ($pacientes as &$p) {
            unset($p['_mtime']);
        }
        unset($p);

        rsort($fechasDisponibles);

        return response()->json([
            'pacientes' => $pacientes,
            'total' => count($pacientes),
            'fechas_disponibles' => array_values(array_unique($fechasDisponibles)),
            'disco_predeterminado' => $this->obtenerDiscoPredeterminado(),
        ]);
    }

    /**
     * Download a specific document belonging to a patient.
     */
    public function descargarArchivo(Request $request, string $fecha, string $paciente, string $archivo): BinaryFileResponse
    {
        $fechaSegura = basename($fecha);
        $pacienteSeguro = basename($paciente);
        $archivoSeguro = basename($archivo);
        $disco = $request->input('disco');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            abort(404, 'Fecha de paciente no válida.');
        }

        $candidatos = [];
        if ($disco) {
            $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        }
        $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}");

        $ruta = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $ruta = $cand;
                break;
            }
        }

        if (! $ruta) {
            abort(404, 'El documento solicitado no existe.');
        }

        return response()->download($ruta, $archivoSeguro, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Download all 3 documents of a patient packaged in a ZIP file.
     */
    public function descargarZip(Request $request, string $fecha, string $paciente): BinaryFileResponse
    {
        $fechaSegura = basename($fecha);
        $pacienteSeguro = basename($paciente);
        $disco = $request->input('disco');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            abort(404, 'Fecha de paciente no válida.');
        }

        $candidatos = [];
        if ($disco) {
            $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}";
        }
        $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}");

        $carpeta = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $carpeta = $cand;
                break;
            }
        }

        if (! $carpeta) {
            abort(404, 'La carpeta del paciente no fue encontrada.');
        }

        $files = File::files($carpeta);
        if (empty($files)) {
            abort(404, 'No hay documentos en la carpeta del paciente.');
        }

        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir, 0755, true);

        $zipFileName = "{$pacienteSeguro}_3_Documentos.zip";
        $zipPath = "{$tempDir}/{$zipFileName}";

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                $zip->addFile($file->getRealPath(), $file->getFilename());
            }
            $zip->close();
        } else {
            abort(500, 'No se pudo generar el archivo ZIP.');
        }

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download a blank template directly from Plantillas/ folder.
     */
    public function descargarPlantilla(string $tipo): BinaryFileResponse
    {
        if (! isset($this->plantillas[$tipo])) {
            abort(404, 'Tipo de plantilla no válido.');
        }

        $info = $this->plantillas[$tipo];
        $ruta = base_path('Plantillas/'.$info['archivo']);

        if (! File::exists($ruta)) {
            abort(404, "La plantilla '{$info['archivo']}' no se encuentra en la carpeta Plantillas.");
        }

        return response()->download($ruta, $info['archivo'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Abre la carpeta de un paciente directamente en el Explorador de Windows.
     */
    public function abrirCarpeta(Request $request): JsonResponse
    {
        $fechaSegura = basename((string) $request->input('fecha', ''));
        $pacienteSeguro = basename((string) $request->input('paciente', ''));
        $disco = (string) $request->input('disco', '');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            return response()->json([
                'success' => false,
                'message' => 'Fecha de paciente no válida.',
            ], 404);
        }

        $candidatos = [];
        if (! empty($disco)) {
            $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}";
        }
        $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
        $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}");

        $rutaReal = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $rutaReal = str_replace('/', '\\', realpath($cand) ?: $cand);
                break;
            }
        }

        if (! $rutaReal) {
            return response()->json([
                'success' => false,
                'message' => 'La carpeta del paciente no fue encontrada en el disco.',
            ], 404);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            @pclose(@popen("cmd.exe /c start explorer.exe \"{$rutaReal}\"", 'r'));
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Carpeta abierta en el Explorador de Windows.',
            'ruta' => $rutaReal,
        ]);
    }

    /**
     * Abre un archivo clínico directamente en el programa predeterminado (Microsoft Word).
     */
    public function abrirArchivo(Request $request): JsonResponse
    {
        $fechaSegura = basename((string) $request->input('fecha', ''));
        $pacienteSeguro = basename((string) $request->input('paciente', ''));
        $archivoSeguro = basename((string) $request->input('archivo', ''));
        $disco = (string) $request->input('disco', '');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            return response()->json([
                'success' => false,
                'message' => 'Fecha de paciente no válida.',
            ], 404);
        }

        $candidatos = [];
        if (! empty($disco)) {
            $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        }
        $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}";
        $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}/{$archivoSeguro}");

        $rutaReal = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $rutaReal = str_replace('/', '\\', realpath($cand) ?: $cand);
                break;
            }
        }

        if (! $rutaReal) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no fue encontrado en el disco.',
            ], 404);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            @pclose(@popen("cmd.exe /c start \"\" \"{$rutaReal}\"", 'r'));
        }

        return response()->json([
            'success' => true,
            'mensaje' => "Archivo '{$archivoSeguro}' abierto en Windows.",
            'ruta' => $rutaReal,
        ]);
    }

    /**
     * Sanitize patient name to safely create folder names.
     */
    protected function sanitizarCarpeta(string $nombre): string
    {
        $nombre = Str::ascii($nombre);
        $nombre = preg_replace('/\s+/', '_', $nombre) ?? '';
        $nombre = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $nombre) ?? '';

        return trim($nombre, '_-.');
    }
}
