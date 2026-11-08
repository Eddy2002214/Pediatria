<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentoController extends Controller
{
    public function __construct(
        protected GoogleDriveService $googleDrive
    ) {}

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
     * Verifica si una letra de disco corresponde a una unidad de Google Drive Desktop.
     */
    public function esGoogleDrive(string $discoLetra): bool
    {
        $letra = strtoupper(trim($discoLetra, ':\\/ '));

        return is_dir("{$letra}:/Mi unidad") || is_dir("{$letra}:/My Drive");
    }

    /**
     * Verifica si una unidad contiene la carpeta o acceso directo a PEDIATRIA.
     */
    public function tienePediatria(string $discoLetra): bool
    {
        $letra = strtoupper(trim($discoLetra, ':\\/ '));
        if (! $this->esGoogleDrive($letra)) {
            return is_dir("{$letra}:/Pediatria");
        }

        $subcarpeta = is_dir("{$letra}:/Mi unidad") ? 'Mi unidad' : 'My Drive';

        // 1. Acceso directo de Google Drive (.shortcut-targets-by-id)
        $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
        if (! empty($folderId)) {
            if (is_dir("{$letra}:/.shortcut-targets-by-id/{$folderId}/PEDIATRIA") || is_dir("{$letra}:/.shortcut-targets-by-id/{$folderId}")) {
                return true;
            }
        }

        $shortcuts = glob("{$letra}:/.shortcut-targets-by-id/*/PEDIATRIA");
        if (! empty($shortcuts) && is_dir($shortcuts[0])) {
            return true;
        }

        // 2. Carpeta física directa
        if (is_dir("{$letra}:/{$subcarpeta}/PEDIATRIA")) {
            return true;
        }

        // 3. Archivo de acceso directo de Windows creado por Google Drive
        return file_exists("{$letra}:/{$subcarpeta}/PEDIATRIA.lnk");
    }

    /**
     * Obtiene la lista de discos del sistema con su estado y ruta a Pediatria.
     * Prioriza Google Drive oficial (H:) como la única unidad de Drive activa para evitar duplicados.
     *
     * @return array<int, array{disco: string, etiqueta: string, es_drive: bool, raiz: string, ruta_pediatria: string, escribible: bool, es_predeterminado: bool}>
     */
    public function obtenerDiscosDisponibles(): array
    {
        $discoPredeterminado = $this->obtenerDiscoPredeterminado();
        $discos = [];

        // 1. Google Drive Oficial o el Google Drive activo del equipo
        if ($this->esGoogleDrive($discoPredeterminado)) {
            $discos[] = [
                'disco' => $discoPredeterminado,
                'etiqueta' => "Google Drive ({$discoPredeterminado}:)",
                'es_drive' => true,
                'raiz' => "{$discoPredeterminado}:\\",
                'ruta_pediatria' => str_replace('/', '\\', $this->getRutaBasePediatria($discoPredeterminado)),
                'escribible' => true,
                'es_predeterminado' => true,
            ];
        }

        // 2. Si existe Disco local D:, permitirlo como respaldo secundario
        if (is_dir('D:/') && @is_writable('D:/')) {
            $discos[] = [
                'disco' => 'D',
                'etiqueta' => count($discos) > 0 ? 'Disco D: (Respaldo)' : 'Disco D: (Automático)',
                'es_drive' => false,
                'raiz' => 'D:\\',
                'ruta_pediatria' => 'D:\\Pediatria',
                'escribible' => true,
                'es_predeterminado' => ($discoPredeterminado === 'D'),
            ];
        }

        // 3. Fallback a Disco C: solo si no hay ni Google Drive ni D:
        if (empty($discos)) {
            $discoBase = strtoupper(substr(base_path(), 0, 1)) ?: 'C';
            $discos[] = [
                'disco' => $discoBase,
                'etiqueta' => "Disco {$discoBase}: (Local)",
                'es_drive' => false,
                'raiz' => "{$discoBase}:\\",
                'ruta_pediatria' => "{$discoBase}:\\Pediatria",
                'escribible' => true,
                'es_predeterminado' => true,
            ];
        }

        return $discos;
    }

    /**
     * Determina el disco predeterminado con reconocimiento automático inteligente:
     * Prioridad 1: Cualquier unidad Google Drive que contenga PEDIATRIA (física o por acceso directo).
     * Prioridad 2: Cualquier otra letra con Google Drive.
     * Prioridad 3: Disco de datos local (D:) si existe en la computadora.
     * Prioridad 4: Disco del sistema o base (C:).
     */
    public function obtenerDiscoPredeterminado(): string
    {
        $configDisco = env('PEDIATRIA_DISCO');
        if ($configDisco && is_dir(strtoupper($configDisco).':/')) {
            $letraConfig = strtoupper($configDisco);
            if ($this->tienePediatria($letraConfig)) {
                return $letraConfig;
            }
        }

        // Buscar primero cualquier unidad de Google Drive que tenga PEDIATRIA (física o por acceso directo)
        foreach (['H', 'G', 'I', 'J', 'K', 'E', 'F'] as $letra) {
            if ($this->esGoogleDrive($letra) && $this->tienePediatria($letra)) {
                return $letra;
            }
        }

        // Si ninguna tiene Pediatria todavía pero hay Google Drive, usar la primera que encontremos
        foreach (['H', 'G', 'I', 'J', 'K', 'E', 'F'] as $letra) {
            if ($this->esGoogleDrive($letra)) {
                return $letra;
            }
        }

        // Prioridad local: Disco D:
        if (is_dir('D:/') && @is_writable('D:/')) {
            return 'D';
        }

        // Prioridad local: Disco del proyecto o C:
        $letraProyecto = strtoupper(substr(base_path(), 0, 1));

        return $letraProyecto ?: 'C';
    }

    /**
     * Resuelve la ruta base para la carpeta Pediatria según el disco elegido.
     * Detecta automáticamente carpetas compartidas agregadas como accesos directos
     * en Google Drive para escritorio (.shortcut-targets-by-id).
     */
    public function getRutaBasePediatria(?string $disco = null): string
    {
        $discoLetra = $disco ? strtoupper(trim($disco, ':\\/ ')) : $this->obtenerDiscoPredeterminado();

        if (! is_dir("{$discoLetra}:/")) {
            $discoLetra = $this->obtenerDiscoPredeterminado();
        }

        // Si es Google Drive (cualquier letra con 'Mi unidad' o 'My Drive', e.g. H: o G: o I:)
        if ($this->esGoogleDrive($discoLetra)) {
            $subcarpeta = is_dir("{$discoLetra}:/Mi unidad") ? 'Mi unidad' : 'My Drive';

            // 1. Prioridad: Verificar si es un acceso directo de Google Drive (.shortcut-targets-by-id)
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            if (! empty($folderId)) {
                $candidatosShortcut = [
                    "{$discoLetra}:/.shortcut-targets-by-id/{$folderId}/PEDIATRIA",
                    "{$discoLetra}:/.shortcut-targets-by-id/{$folderId}",
                ];
                foreach ($candidatosShortcut as $cand) {
                    if (is_dir($cand)) {
                        return $cand;
                    }
                }
            }

            $shortcutTargets = glob("{$discoLetra}:/.shortcut-targets-by-id/*/PEDIATRIA");
            if (! empty($shortcutTargets) && is_dir($shortcutTargets[0])) {
                return $shortcutTargets[0];
            }

            // 2. Si no es un acceso directo, verificar si existe la carpeta física normal en Mi unidad
            $rutaPediatriaDrive = "{$discoLetra}:/{$subcarpeta}/PEDIATRIA";
            if (is_dir($rutaPediatriaDrive)) {
                return $rutaPediatriaDrive;
            }

            // 3. Si no existe ninguno y tampoco hay acceso directo .lnk, crear la carpeta física
            $rutaLnk = "{$discoLetra}:/{$subcarpeta}/PEDIATRIA.lnk";
            if (! file_exists($rutaLnk)) {
                @File::ensureDirectoryExists($rutaPediatriaDrive, 0755, true);
            }

            return $rutaPediatriaDrive;
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

        // Clonar plantillas desde la carpeta PLANTILLAS de Google Drive si está disponible
        $carpetaPlantillasDrive = "{$rutaBase}/PLANTILLAS";

        if (File::isDirectory($carpetaPlantillasDrive)) {
            $archivosPlantilla = File::files($carpetaPlantillasDrive);
            foreach ($archivosPlantilla as $file) {
                $nombreArchivo = $file->getFilename();

                // Ignorar archivos ocultos o de sistema (desktop.ini, temporales ~$)
                if (str_starts_with($nombreArchivo, '.') || str_starts_with($nombreArchivo, '~') || strcasecmp($nombreArchivo, 'desktop.ini') === 0) {
                    continue;
                }

                // Normalizar nombres a los oficiales para garantizar consistencia médica
                $nombreOficial = $nombreArchivo;
                $lower = mb_strtolower($nombreArchivo);
                if (str_contains($lower, 'historia') || str_contains($lower, 'clinica')) {
                    $nombreOficial = 'Historia_Clinica.docx';
                } elseif (str_contains($lower, 'indicacion') || str_contains($lower, 'indicaciones') || str_contains($lower, 'inducacion')) {
                    $nombreOficial = 'Indicaciones.docx';
                } elseif (str_contains($lower, 'admision') || str_contains($lower, 'arixa') || str_contains($lower, 'adm')) {
                    $nombreOficial = 'Admision.docx';
                }

                $rutaDestinoArchivo = "{$carpetaDestino}/{$nombreOficial}";

                copy($file->getRealPath(), $rutaDestinoArchivo);

                $bytes = filesize($rutaDestinoArchivo);
                $tamano = $bytes >= 1048576
                    ? number_format($bytes / 1048576, 2).' MB'
                    : number_format($bytes / 1024, 0).' KB';

                $nombreLimpio = pathinfo($nombreOficial, PATHINFO_FILENAME);
                $archivosGenerados[] = [
                    'tipo' => Str::slug($nombreLimpio),
                    'titulo' => str_replace('_', ' ', $nombreLimpio),
                    'nombre' => $nombreOficial,
                    'tamano' => $tamano,
                    'url' => route('documentos.descargar-archivo', [
                        'fecha' => $fechaHoy,
                        'paciente' => $pacienteDir,
                        'archivo' => $nombreOficial,
                        'disco' => $letraDisco,
                    ], false),
                ];
            }
        }

        // Si no se encontraron plantillas en Drive, usar las plantillas locales por defecto
        if (empty($archivosGenerados)) {
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
                        ], false),
                    ];
                } else {
                    $plantillasFaltantes[] = $info['archivo'];
                }
            }
        }

        $driveInfo = null;
        if ($this->esGoogleDrive($letraDisco)) {
            $driveInfo = [
                'sincronizado' => true,
                'metodo' => 'google_drive_desktop',
                'mensaje' => "Guardado y sincronizado automáticamente en Google Drive ({$letraDisco}:)",
            ];
        } elseif ($this->googleDrive->isConfigured()) {
            try {
                $driveResult = $this->googleDrive->ingresarPacienteDrive($nombreInput, $fechaHoy);
                $driveInfo = [
                    'sincronizado' => true,
                    'carpeta_id' => $driveResult['carpeta_id'],
                    'carpeta_url' => $driveResult['carpeta_url'],
                    'archivos' => $driveResult['archivos'],
                ];
            } catch (\Throwable $e) {
                Log::warning('Error sincronizando con Google Drive API: '.$e->getMessage());
                $driveInfo = [
                    'sincronizado' => false,
                    'error' => $e->getMessage(),
                ];
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
            'ruta_relativa' => str_replace('/', '\\', $carpetaDestino),
            'archivos' => $archivosGenerados,
            'plantillas_faltantes' => $plantillasFaltantes,
            'zip_url' => route('documentos.descargar-zip', [
                'fecha' => $fechaHoy,
                'paciente' => $pacienteDir,
                'disco' => $letraDisco,
            ], false),
            'drive' => $driveInfo,
        ]);
    }

    /**
     * Devuelve el estado actual de la conexión con Google Drive.
     */
    public function driveStatus(): JsonResponse
    {
        $configurado = $this->googleDrive->isConfigured();
        $plantillasDrive = [];
        $error = null;

        if ($configurado) {
            try {
                $plantillasDrive = $this->googleDrive->getTemplateFiles();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return response()->json([
            'configurado' => $configurado,
            'carpeta_pediatria_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
            'carpeta_plantillas_id' => env('GOOGLE_DRIVE_TEMPLATES_FOLDER_ID'),
            'plantillas_encontradas' => $plantillasDrive,
            'total_plantillas' => count($plantillasDrive),
            'drive_url' => 'https://drive.google.com/drive/folders/'.env('GOOGLE_DRIVE_FOLDER_ID'),
            'error' => $error,
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
            // Usar únicamente el disco predeterminado actual para no mezclar pacientes de otras cuentas o discos
            $rutasPediatria[] = $this->getRutaBasePediatria();
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
                        $nombreArchivo = $file->getFilename();

                        if (str_starts_with($nombreArchivo, '.') || str_starts_with($nombreArchivo, '~') || strcasecmp($nombreArchivo, 'desktop.ini') === 0) {
                            continue;
                        }

                        $bytes = $file->getSize();
                        $tamano = $bytes >= 1048576
                            ? number_format($bytes / 1048576, 2).' MB'
                            : number_format($bytes / 1024, 0).' KB';

                        $archivos[] = [
                            'nombre' => $nombreArchivo,
                            'tamano' => $tamano,
                            'hora' => date('H:i', $file->getMTime()),
                            'url' => route('documentos.descargar-archivo', [
                                'fecha' => $fecha,
                                'paciente' => $pacienteNombreCarpeta,
                                'archivo' => $file->getFilename(),
                                'disco' => $discoLetra,
                            ], false),
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
                        'ruta_relativa' => str_replace('/', '\\', $carpetaPaciente),
                        'total_archivos' => count($archivos),
                        'archivos' => $archivos,
                        'zip_url' => route('documentos.descargar-zip', [
                            'fecha' => $fecha,
                            'paciente' => $pacienteNombreCarpeta,
                            'disco' => $discoLetra,
                        ], false),
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
