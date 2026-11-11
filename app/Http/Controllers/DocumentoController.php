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

        // Clonar las 3 plantillas oficiales maestras (prioridad Google Drive compartido, respaldo local)
        foreach ($this->plantillas as $tipo => $info) {
            $nombreOficial = $info['archivo'];
            $rutaDestinoArchivo = "{$carpetaDestino}/{$nombreOficial}";

            // 1. Buscar primero en la carpeta compartida de Google Drive
            $rutaOrigen = $this->encontrarPlantillaDrive($tipo, $letraDisco);

            // 2. Si no se encuentra en Drive, recurrir a la plantilla local en Plantillas/
            if (! $rutaOrigen || ! File::exists($rutaOrigen)) {
                $rutaLocal = base_path('Plantillas/'.$info['archivo']);
                if (File::exists($rutaLocal)) {
                    $rutaOrigen = $rutaLocal;
                }
            }

            if ($rutaOrigen && File::exists($rutaOrigen)) {
                copy($rutaOrigen, $rutaDestinoArchivo);

                $bytes = filesize($rutaDestinoArchivo);
                $tamano = $bytes >= 1048576
                    ? number_format($bytes / 1048576, 2).' MB'
                    : number_format($bytes / 1024, 0).' KB';

                $nombreLimpio = pathinfo($nombreOficial, PATHINFO_FILENAME);
                $archivosGenerados[] = [
                    'tipo' => $tipo,
                    'titulo' => $info['titulo'],
                    'nombre' => $nombreOficial,
                    'tamano' => $tamano,
                    'url' => route('documentos.descargar-archivo', [
                        'fecha' => $fechaHoy,
                        'paciente' => $pacienteDir,
                        'archivo' => $nombreOficial,
                        'disco' => $letraDisco,
                    ], false),
                ];
            } else {
                $plantillasFaltantes[] = $info['archivo'];
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

        $rutaDirecta = trim((string) $request->input('ruta_carpeta', $request->input('ruta', '')));
        $candidatos = [];
        if (! empty($rutaDirecta) && File::exists($rutaDirecta)) {
            $candidatos[] = $rutaDirecta;
        }
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
     * Obtiene el mapeo de nombres de archivos de plantillas maestras guardado.
     *
     * @return array<string, string>
     */
    protected function obtenerMapeoPlantillas(): array
    {
        $ruta = storage_path('app/plantillas_mapping.json');
        if (File::exists($ruta)) {
            $json = json_decode(File::get($ruta), true);
            if (is_array($json)) {
                return $json;
            }
        }

        return [];
    }

    /**
     * Guarda el nombre del archivo asignado a una plantilla maestra.
     */
    protected function guardarMapeoPlantilla(string $tipo, string $nombreArchivo): void
    {
        $ruta = storage_path('app/plantillas_mapping.json');
        $mapping = $this->obtenerMapeoPlantillas();
        $mapping[$tipo] = $nombreArchivo;
        File::ensureDirectoryExists(dirname($ruta), 0755, true);
        File::put($ruta, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Encuentra la ruta absoluta del archivo de plantilla en Google Drive (o disco configurado) según el tipo.
     */
    public function encontrarPlantillaDrive(string $tipo, ?string $disco = null): ?string
    {
        $rutaBase = $this->getRutaBasePediatria($disco);
        $carpetaDrive = "{$rutaBase}/PLANTILLAS";

        if (! File::isDirectory($carpetaDrive)) {
            return null;
        }

        // 1. Prioridad: Verificar si hay un archivo mapeado y guardado previamente
        $mapping = $this->obtenerMapeoPlantillas();
        if (! empty($mapping[$tipo])) {
            $rutaMapeada = "{$carpetaDrive}/{$mapping[$tipo]}";
            if (File::exists($rutaMapeada)) {
                return realpath($rutaMapeada) ?: $rutaMapeada;
            }
        }

        // 2. Buscar por patrones de nombres de archivo
        $archivos = File::files($carpetaDrive);

        foreach ($archivos as $file) {
            $nombre = $file->getFilename();
            if (str_starts_with($nombre, '.') || str_starts_with($nombre, '~') || strcasecmp($nombre, 'desktop.ini') === 0) {
                continue;
            }

            $lower = mb_strtolower($nombre);

            if ($tipo === 'historia-clinica') {
                if (str_contains($lower, 'historia') || str_contains($lower, 'clinica') || str_contains($lower, 'zoe') || $lower === 'historia_clinica.docx') {
                    return $file->getRealPath();
                }
            } elseif ($tipo === 'indicaciones') {
                if (str_contains($lower, 'indicacion') || str_contains($lower, 'indicaciones') || str_contains($lower, 'inducacion') || $lower === 'indicaciones.docx') {
                    return $file->getRealPath();
                }
            } elseif ($tipo === 'admision') {
                if (str_contains($lower, 'admision') || str_contains($lower, 'admisión') || str_contains($lower, 'arixa') || str_contains($lower, 'adm') || str_contains($lower, 'nota_admision') || $lower === 'admision.docx') {
                    return $file->getRealPath();
                }
            }
        }

        // 3. Si no coincide por nombre especial, buscar con el nombre oficial estándar
        if (isset($this->plantillas[$tipo])) {
            $rutaOficial = "{$carpetaDrive}/{$this->plantillas[$tipo]['archivo']}";
            if (File::exists($rutaOficial)) {
                return realpath($rutaOficial) ?: $rutaOficial;
            }
        }

        return null;
    }

    /**
     * Devuelve el estado y detalle de cada una de las 3 plantillas maestras oficiales.
     *
     * @return array<string, array{tipo: string, titulo: string, descripcion: string, archivo_oficial: string, archivo_real: string, origen: string, ruta: string, fecha_mod: string, existe: bool}>
     */
    public function obtenerEstadoPlantillas(?string $disco = null): array
    {
        $resultado = [];
        $discoLetra = $disco ? strtoupper(trim($disco, ':\\/ ')) : $this->obtenerDiscoPredeterminado();

        foreach ($this->plantillas as $tipo => $info) {
            $rutaDrive = $this->encontrarPlantillaDrive($tipo, $discoLetra);
            $rutaLocal = base_path('Plantillas/'.$info['archivo']);

            $rutaFinal = null;
            $origen = 'No encontrada';
            $nombreReal = $info['archivo'];
            $fechaMod = null;
            $existe = false;

            if ($rutaDrive && File::exists($rutaDrive)) {
                $rutaFinal = $rutaDrive;
                $nombreReal = basename($rutaDrive);
                $origen = 'Google Drive (Compartido)';
                $fechaMod = date('d/m/Y H:i', filemtime($rutaDrive));
                $existe = true;
            } elseif (File::exists($rutaLocal)) {
                $rutaFinal = $rutaLocal;
                $mapping = $this->obtenerMapeoPlantillas();
                $nombreReal = ! empty($mapping[$tipo]) ? $mapping[$tipo] : $info['archivo'];
                $origen = 'Local (Plantillas/)';
                $fechaMod = date('d/m/Y H:i', filemtime($rutaLocal));
                $existe = true;
            }

            $resultado[$tipo] = [
                'tipo' => $tipo,
                'titulo' => $info['titulo'],
                'descripcion' => $info['descripcion'],
                'archivo_oficial' => $info['archivo'],
                'archivo_real' => $nombreReal,
                'origen' => $origen,
                'ruta' => $rutaFinal ? str_replace('/', '\\', realpath($rutaFinal) ?: $rutaFinal) : '',
                'fecha_mod' => $fechaMod ?: 'Sin fecha',
                'existe' => $existe,
            ];
        }

        return $resultado;
    }

    /**
     * Endpoint JSON para consultar el estado actual de las plantillas maestras.
     */
    public function estadoPlantillasJson(Request $request): JsonResponse
    {
        $disco = (string) $request->input('disco', $this->obtenerDiscoPredeterminado());

        return response()->json([
            'success' => true,
            'plantillas' => $this->obtenerEstadoPlantillas($disco),
        ]);
    }

    /**
     * Download a blank template directly from Google Drive or Plantillas/ folder.
     */
    public function descargarPlantilla(Request $request, string $tipo): BinaryFileResponse
    {
        if (! isset($this->plantillas[$tipo])) {
            abort(404, 'Tipo de plantilla no válido.');
        }

        $info = $this->plantillas[$tipo];
        $disco = (string) $request->input('disco', $this->obtenerDiscoPredeterminado());

        $rutaDescarga = $this->encontrarPlantillaDrive($tipo, $disco);

        if (! $rutaDescarga || ! File::exists($rutaDescarga)) {
            $rutaDescarga = base_path('Plantillas/'.$info['archivo']);
        }

        if (! File::exists($rutaDescarga)) {
            abort(404, "La plantilla '{$info['archivo']}' no se encuentra en el servidor ni en Google Drive.");
        }

        $nombreDescarga = basename($rutaDescarga);

        return response()->download($rutaDescarga, $nombreDescarga, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Abre la plantilla maestra directamente en Microsoft Word para su edición.
     */
    public function abrirPlantilla(Request $request): JsonResponse
    {
        $tipo = (string) $request->input('tipo', '');
        if (! isset($this->plantillas[$tipo])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de plantilla no válido.',
            ], 422);
        }

        $info = $this->plantillas[$tipo];
        $disco = (string) $request->input('disco', $this->obtenerDiscoPredeterminado());

        // 1. Prioridad: Buscar en la carpeta compartida de Google Drive
        $rutaAbrir = $this->encontrarPlantillaDrive($tipo, $disco);

        // 2. Fallback: Buscar en la carpeta local Plantillas/
        if (! $rutaAbrir || ! File::exists($rutaAbrir)) {
            $rutaLocal = base_path('Plantillas/'.$info['archivo']);
            if (File::exists($rutaLocal)) {
                $rutaAbrir = $rutaLocal;
            }
        }

        if (! $rutaAbrir || ! File::exists($rutaAbrir)) {
            return response()->json([
                'success' => false,
                'message' => "La plantilla '{$info['archivo']}' no se encuentra en Google Drive ni en la carpeta local.",
            ], 404);
        }

        $rutaReal = str_replace('/', '\\', realpath($rutaAbrir) ?: $rutaAbrir);
        $nombreReal = basename($rutaReal);
        $esDrive = str_contains($rutaReal, 'PLANTILLAS') || $this->esGoogleDrive($disco);

        if (PHP_OS_FAMILY === 'Windows') {
            try {
                pclose(popen("cmd.exe /c start \"\" \"{$rutaReal}\"", 'r'));

                $origenTexto = $esDrive ? "Google Drive ({$nombreReal})" : "Local ({$nombreReal})";

                return response()->json([
                    'success' => true,
                    'message' => "✓ Plantilla '{$info['titulo']}' abierta en Word desde {$origenTexto}. Modifica el documento y presiona Ctrl+S para guardar los cambios.",
                    'ruta' => $rutaReal,
                    'archivo' => $nombreReal,
                    'origen' => $origenTexto,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al ejecutar Word: '.$e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Apertura directa solo disponible en entorno local de Windows.',
        ], 400);
    }

    /**
     * Reemplaza una plantilla maestra subiendo un nuevo archivo .docx.
     */
    public function subirPlantilla(Request $request): JsonResponse
    {
        $tipo = (string) $request->input('tipo', '');
        if (! isset($this->plantillas[$tipo])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de plantilla no válido.',
            ], 422);
        }

        $request->validate([
            'archivo' => 'required|file|max:20480',
        ]);

        $file = $request->file('archivo');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'docx') {
            return response()->json([
                'success' => false,
                'message' => 'El archivo debe ser un documento Word (.docx).',
            ], 422);
        }

        $info = $this->plantillas[$tipo];
        $disco = (string) $request->input('disco', $this->obtenerDiscoPredeterminado());
        $nombreArchivoNuevo = basename($file->getClientOriginalName());

        // 1. Guardar copia de respaldo en carpeta local Plantillas/
        $carpetaLocal = base_path('Plantillas');
        File::ensureDirectoryExists($carpetaLocal, 0755, true);
        $rutaLocal = "{$carpetaLocal}/{$info['archivo']}";
        File::copy($file->getRealPath(), $rutaLocal);
        $this->guardarMapeoPlantilla($tipo, $nombreArchivoNuevo);

        $actualizadoEnDrive = false;
        $archivoDriveNombre = $nombreArchivoNuevo;

        // 2. Actualizar en la unidad de Google Drive Desktop
        $rutaBase = $this->getRutaBasePediatria($disco);
        $carpetaDrive = "{$rutaBase}/PLANTILLAS";

        if (File::isDirectory($carpetaDrive)) {
            $archivoDriveExistente = $this->encontrarPlantillaDrive($tipo, $disco);
            $rutaNuevoDrive = "{$carpetaDrive}/{$nombreArchivoNuevo}";

            if ($archivoDriveExistente && File::exists($archivoDriveExistente)) {
                // Si el nombre anterior era diferente, eliminar el archivo antiguo para renombrarlo limpiamente
                if (strcasecmp(basename($archivoDriveExistente), $nombreArchivoNuevo) !== 0) {
                    File::copy($file->getRealPath(), $rutaNuevoDrive);
                    @touch($rutaNuevoDrive);
                    @File::delete($archivoDriveExistente);
                } else {
                    File::copy($file->getRealPath(), $archivoDriveExistente);
                    @touch($archivoDriveExistente);
                }
            } else {
                File::copy($file->getRealPath(), $rutaNuevoDrive);
                @touch($rutaNuevoDrive);
            }

            $this->guardarMapeoPlantilla($tipo, $nombreArchivoNuevo);
            $actualizadoEnDrive = true;
        }

        // 3. Sincronizar y renombrar también en Google Drive API si está configurada
        try {
            if ($this->googleDrive->isConfigured()) {
                $this->googleDrive->updateTemplateFile($tipo, $file->getRealPath(), $nombreArchivoNuevo);
            }
        } catch (\Throwable $e) {
            Log::warning("Error sincronizando plantilla {$tipo} con Google Drive API: ".$e->getMessage());
        }

        $mensaje = $actualizadoEnDrive
            ? "✓ Plantilla '{$info['titulo']}' actualizada y renombrada a '{$nombreArchivoNuevo}' en Google Drive. A partir de ahora, todo nuevo paciente usará este formato."
            : "✓ Plantilla '{$info['titulo']}' actualizada con éxito localmente.";

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'archivo' => $nombreArchivoNuevo,
            'estado' => $this->obtenerEstadoPlantillas($disco),
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
        $rutaDirecta = trim((string) $request->input('ruta', $request->input('ruta_carpeta', '')));

        $rutaReal = null;

        // 1. Si viene la ruta absoluta directa validada
        if (! empty($rutaDirecta) && File::exists($rutaDirecta)) {
            $rutaReal = str_replace('/', '\\', realpath($rutaDirecta) ?: $rutaDirecta);
        }

        // 2. Si no, buscar en todos los candidatos posibles
        if (! $rutaReal && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            $candidatos = [];
            if (! empty($disco)) {
                $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/My Drive/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            }
            $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "G:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "G:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "H:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "H:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}");

            foreach ($candidatos as $cand) {
                if (File::exists($cand)) {
                    $rutaReal = str_replace('/', '\\', realpath($cand) ?: $cand);
                    break;
                }
            }
        }

        if (! $rutaReal) {
            return response()->json([
                'success' => false,
                'message' => 'La carpeta del paciente no fue encontrada en el disco.',
            ], 404);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            @pclose(@popen("explorer.exe \"{$rutaReal}\"", 'r'));
            @pclose(@popen("cmd.exe /c start \"\" \"{$rutaReal}\"", 'r'));
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
        $rutaDirecta = trim((string) $request->input('ruta', $request->input('ruta_carpeta', '')));

        if (empty($archivoSeguro)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no especificado.',
            ], 422);
        }

        $rutaReal = null;
        $carpetaPaciente = null;

        // 1. Si viene la ruta absoluta directa de la carpeta
        if (! empty($rutaDirecta) && File::isDirectory($rutaDirecta)) {
            $carpetaPaciente = $rutaDirecta;
            $archivoDirecto = "{$rutaDirecta}/{$archivoSeguro}";
            if (File::exists($archivoDirecto)) {
                $rutaReal = str_replace('/', '\\', realpath($archivoDirecto) ?: $archivoDirecto);
            }
        }

        // 2. Si no, buscar la carpeta del paciente en todos los candidatos posibles
        if (! $carpetaPaciente && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSegura)) {
            $candidatos = [];
            if (! empty($disco)) {
                $candidatos[] = $this->getRutaBasePediatria($disco)."/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
                $candidatos[] = "{$disco}:/My Drive/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            }
            $candidatos[] = $this->getRutaBasePediatria()."/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "G:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "G:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "H:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "H:/Mi unidad/PEDIATRIA/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "D:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = "C:/Pediatria/{$fechaSegura}/{$pacienteSeguro}";
            $candidatos[] = base_path("Pediatria/{$fechaSegura}/{$pacienteSeguro}");

            foreach ($candidatos as $cand) {
                if (File::isDirectory($cand)) {
                    $carpetaPaciente = $cand;
                    $archivoDirecto = "{$cand}/{$archivoSeguro}";
                    if (File::exists($archivoDirecto)) {
                        $rutaReal = str_replace('/', '\\', realpath($archivoDirecto) ?: $archivoDirecto);
                    }
                    break;
                }
            }
        }

        // 3. Coincidencia inteligente: si el archivo fue guardado con otro nombre en la carpeta del paciente
        if (! $rutaReal && $carpetaPaciente && File::isDirectory($carpetaPaciente)) {
            $archivosCarpeta = File::files($carpetaPaciente);
            $lowerBuscado = mb_strtolower($archivoSeguro);

            foreach ($archivosCarpeta as $f) {
                $nombreF = $f->getFilename();
                if (str_starts_with($nombreF, '.') || str_starts_with($nombreF, '~') || strcasecmp($nombreF, 'desktop.ini') === 0) {
                    continue;
                }
                $lowerF = mb_strtolower($nombreF);

                if ((str_contains($lowerBuscado, 'admision') || str_contains($lowerBuscado, 'admisión')) &&
                    (str_contains($lowerF, 'admision') || str_contains($lowerF, 'admisión') || str_contains($lowerF, 'adm') || str_contains($lowerF, 'arixa'))) {
                    $rutaReal = str_replace('/', '\\', $f->getRealPath());
                    break;
                }

                if ((str_contains($lowerBuscado, 'historia') || str_contains($lowerBuscado, 'clinica')) &&
                    (str_contains($lowerF, 'historia') || str_contains($lowerF, 'clinica') || str_contains($lowerF, 'zoe'))) {
                    $rutaReal = str_replace('/', '\\', $f->getRealPath());
                    break;
                }

                if ((str_contains($lowerBuscado, 'indicacion') || str_contains($lowerBuscado, 'indicaciones')) &&
                    (str_contains($lowerF, 'indicacion') || str_contains($lowerF, 'indicaciones') || str_contains($lowerF, 'inducacion'))) {
                    $rutaReal = str_replace('/', '\\', $f->getRealPath());
                    break;
                }
            }
        }

        // 4. Auto-reparación (Auto-heal): Si el archivo clínico faltaba en la carpeta del paciente, regenerarlo desde la plantilla
        if (! $rutaReal && $carpetaPaciente && File::isDirectory($carpetaPaciente)) {
            $tipo = null;
            $lowerBuscado = mb_strtolower($archivoSeguro);
            if (str_contains($lowerBuscado, 'historia') || str_contains($lowerBuscado, 'clinica')) {
                $tipo = 'historia-clinica';
            } elseif (str_contains($lowerBuscado, 'indicacion')) {
                $tipo = 'indicaciones';
            } elseif (str_contains($lowerBuscado, 'admision') || str_contains($lowerBuscado, 'admisión') || str_contains($lowerBuscado, 'adm')) {
                $tipo = 'admision';
            }

            if ($tipo) {
                $rutaPlantilla = $this->encontrarPlantillaDrive($tipo, $disco) ?: base_path('Plantillas/'.$this->plantillas[$tipo]['archivo']);
                if (File::exists($rutaPlantilla)) {
                    $archivoDestino = "{$carpetaPaciente}/{$archivoSeguro}";
                    File::copy($rutaPlantilla, $archivoDestino);
                    $rutaReal = str_replace('/', '\\', realpath($archivoDestino) ?: $archivoDestino);
                }
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
     * Extrae el texto de todos los documentos .docx del paciente para NotebookLM u otras IAs.
     */
    public function extraerTextoPaciente(Request $request): JsonResponse
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

        $rutaDirecta = trim((string) $request->input('ruta_carpeta', $request->input('ruta', '')));
        $candidatos = [];
        if (! empty($rutaDirecta) && File::exists($rutaDirecta)) {
            $candidatos[] = $rutaDirecta;
        }
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
            foreach (['G', 'H', 'D', 'C', 'I', 'J', 'E', 'F'] as $letra) {
                if (is_dir("{$letra}:/")) {
                    $rutaAlt = $this->getRutaBasePediatria($letra)."/{$fechaSegura}/{$pacienteSeguro}";
                    if (File::exists($rutaAlt)) {
                        $carpeta = $rutaAlt;
                        break;
                    }
                }
            }
        }

        if (! $carpeta) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la carpeta del paciente.',
            ], 404);
        }

        $archivos = File::files($carpeta);
        $documentosExtraidos = [];
        $nombreLegible = str_replace(['_', '-'], ' ', $pacienteSeguro);

        $ordenPreferido = ['Historia_Clinica.docx', 'Indicaciones.docx', 'Admision.docx'];

        // Ordenar archivos para que Historia Clínica vaya primero
        usort($archivos, function ($a, $b) use ($ordenPreferido) {
            $idxA = array_search($a->getFilename(), $ordenPreferido);
            $idxB = array_search($b->getFilename(), $ordenPreferido);
            if ($idxA !== false && $idxB !== false) {
                return $idxA <=> $idxB;
            }
            if ($idxA !== false) {
                return -1;
            }
            if ($idxB !== false) {
                return 1;
            }

            return strcmp($a->getFilename(), $b->getFilename());
        });

        $seccionesTexto = [];
        $archivosInfo = [];

        foreach ($archivos as $archivo) {
            $nombreArchivo = $archivo->getFilename();
            if (str_starts_with($nombreArchivo, '.') || str_starts_with($nombreArchivo, '~')) {
                continue;
            }

            $ext = strtolower($archivo->getExtension());
            if ($ext !== 'docx') {
                continue;
            }

            $texto = $this->extraerTextoDocx($archivo->getRealPath());
            $titulo = str_replace(['_', '.docx'], [' ', ''], $nombreArchivo);

            $documentosExtraidos[] = [
                'nombre' => $nombreArchivo,
                'titulo' => $titulo,
                'tamano' => number_format($archivo->getSize() / 1024, 0).' KB',
                'caracteres' => mb_strlen($texto),
                'texto' => $texto,
            ];

            $archivosInfo[] = [
                'nombre' => $nombreArchivo,
                'url' => route('documentos.descargar-archivo', [
                    'fecha' => $fechaSegura,
                    'paciente' => $pacienteSeguro,
                    'archivo' => $nombreArchivo,
                    'disco' => $disco ? $this->extraerLetraDisco($carpeta) : null,
                ], false),
            ];

            $seccionesTexto[] = "================================================================================\n"
                .'DOCUMENTO: '.strtoupper($titulo)."\n"
                ."ARCHIVO: {$nombreArchivo}\n"
                ."================================================================================\n\n"
                .($texto ?: '(Sin contenido de texto detectado)')."\n";
        }

        $encabezado = "================================================================================\n"
            ."EXPEDIENTE CLÍNICO PEDIÁTRICO COMPLETO PARA NOTEBOOKLM\n"
            .'PACIENTE: '.strtoupper($nombreLegible)."\n"
            ."FECHA DE INGRESO: {$fechaSegura}\n"
            .'TOTAL DE DOCUMENTOS: '.count($documentosExtraidos)."\n"
            ."================================================================================\n\n";

        $textoCompleto = $encabezado.implode("\n\n", $seccionesTexto);

        $promptSugerido = "Actúa como médico especialista en pediatría asistencial y analiza a fondo el expediente clínico adjunto del paciente {$nombreLegible} (Fecha: {$fechaSegura}).\n\n"
            ."Genera un informe clínico estructurado con los siguientes puntos:\n"
            ."1. Resumen de motivo de consulta, antecedentes relevantes y diagnóstico principal/diferencial.\n"
            ."2. Cronología clínica y signos vitales registrados.\n"
            ."3. Esquema terapéutico activo e indicaciones médicas (medicamento, dosis en mg/kg, vía e intervalo horario).\n"
            .'4. Alertas farmacológicas, contraindicaciones, signos de alarma y pendientes críticos para el pase de guardia.';

        return response()->json([
            'success' => true,
            'paciente' => $nombreLegible,
            'paciente_carpeta' => $pacienteSeguro,
            'fecha' => $fechaSegura,
            'total_documentos' => count($documentosExtraidos),
            'documentos' => $documentosExtraidos,
            'archivos' => $archivosInfo,
            'texto_completo' => $textoCompleto,
            'prompt_sugerido' => $promptSugerido,
            'ruta_carpeta' => str_replace('/', '\\', $carpeta),
            'zip_url' => route('documentos.descargar-zip', [
                'fecha' => $fechaSegura,
                'paciente' => $pacienteSeguro,
                'disco' => $disco ? $this->extraerLetraDisco($carpeta) : null,
            ], false),
        ]);
    }

    /**
     * Extrae el texto plano de un archivo .docx comprimido en OpenXML.
     */
    private function extraerTextoDocx(string $rutaDocx): string
    {
        if (! file_exists($rutaDocx)) {
            return '';
        }

        $zip = new ZipArchive;
        if ($zip->open($rutaDocx) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            return '';
        }

        // Separar celdas y filas de tablas en Word (<w:tc>, <w:tr>)
        $xml = preg_replace('/<\/w:tc>/', '  ', $xml);
        $xml = preg_replace('/<\/w:tr>/', "\n", $xml);
        // Reemplazar saltos de párrafo en Word (<w:p>) por saltos de línea
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        // Reemplazar saltos de línea en Word (<w:br/>) por saltos de línea
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml);
        // Reemplazar pestañas en Word (<w:tab/>) por espacio
        $xml = preg_replace('/<w:tab[^>]*\/>/', ' ', $xml);

        $texto = strip_tags($xml);
        // Limpiar múltiples saltos de línea seguidos
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        return trim($texto);
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
