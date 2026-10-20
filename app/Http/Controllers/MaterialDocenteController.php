<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialDocenteController extends Controller
{
    /**
     * Extensiones válidas para presentaciones de PowerPoint y material docente.
     *
     * @var array<int, string>
     */
    protected array $extensionesValidas = ['pptx', 'ppt', 'ppsx', 'pps', 'potx', 'pot', 'odp', 'pdf'];

    public function __construct(
        public DocumentoController $documentoController
    ) {}

    /**
     * Sanitiza nombres de temas o archivos para evitar vulnerabilidades de path traversal.
     */
    public function sanitizarNombre(string $nombre): string
    {
        $nombre = Str::ascii($nombre);
        $nombre = preg_replace('/\s+/', '_', $nombre) ?? '';
        $nombre = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $nombre) ?? '';

        return trim($nombre, '_-.');
    }

    /**
     * Obtiene las rutas candidatas donde se almacena el material docente en PowerPoint.
     *
     * @return array<int, string>
     */
    public function obtenerRutasBase(string $disco = ''): array
    {
        $candidatos = [];

        if (! empty($disco)) {
            $candidatos[] = $this->documentoController->getRutaBasePediatria($disco).'/MaterialDocente/PowerPoint';
        }

        $candidatos[] = $this->documentoController->getRutaBasePediatria().'/MaterialDocente/PowerPoint';
        $candidatos[] = 'D:/Pediatria/MaterialDocente/PowerPoint';
        $candidatos[] = 'C:/Pediatria/MaterialDocente/PowerPoint';
        $candidatos[] = base_path('Pediatria/MaterialDocente/PowerPoint');
        $candidatos[] = base_path('MaterialDocente/PowerPoint');

        return array_values(array_unique($candidatos));
    }

    /**
     * Lista todas las presentaciones disponibles con soporte para búsqueda y filtrado por tema.
     */
    public function listar(Request $request): JsonResponse
    {
        $filtroTema = trim((string) $request->input('tema', ''));
        $filtroBuscar = mb_strtolower(trim((string) $request->input('q', '')));
        $discoSeleccionado = (string) $request->input('disco', '');

        $rutas = $this->obtenerRutasBase($discoSeleccionado);
        $rutaPrincipal = $this->documentoController->getRutaBasePediatria($discoSeleccionado).'/MaterialDocente/PowerPoint';

        // Asegurar que exista la carpeta principal
        File::ensureDirectoryExists($rutaPrincipal, 0755, true);

        $presentaciones = [];
        $temasEncontrados = [];
        $rutasEscaneadas = [];

        foreach ($rutas as $rutaBase) {
            if (! File::isDirectory($rutaBase)) {
                continue;
            }

            $realBase = realpath($rutaBase) ?: $rutaBase;
            if (in_array($realBase, $rutasEscaneadas, true)) {
                continue;
            }
            $rutasEscaneadas[] = $realBase;

            $letraDisco = $this->documentoController->extraerLetraDisco($rutaBase);

            // 1. Archivos directos en la raíz de PowerPoint/ (Tema: General)
            $archivosRaiz = File::files($rutaBase);
            foreach ($archivosRaiz as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, $this->extensionesValidas, true)) {
                    continue;
                }

                $tema = 'General';
                $temasEncontrados[$tema] = true;

                $nombreArchivo = $file->getFilename();
                $nombreSinExt = pathinfo($nombreArchivo, PATHINFO_FILENAME);
                $tituloLegible = str_replace(['_', '-'], ' ', $nombreSinExt);

                $presentaciones[] = $this->formatearPresentacion(
                    $file,
                    $tema,
                    $tituloLegible,
                    $letraDisco,
                    "Pediatria/MaterialDocente/PowerPoint/{$nombreArchivo}"
                );
            }

            // 2. Subcarpetas por Tema
            $subcarpetas = File::directories($rutaBase);
            foreach ($subcarpetas as $subcarpeta) {
                $tema = basename($subcarpeta);
                $temasEncontrados[$tema] = true;

                $archivosTema = File::files($subcarpeta);
                foreach ($archivosTema as $file) {
                    $ext = strtolower($file->getExtension());
                    if (! in_array($ext, $this->extensionesValidas, true)) {
                        continue;
                    }

                    $nombreArchivo = $file->getFilename();
                    $nombreSinExt = pathinfo($nombreArchivo, PATHINFO_FILENAME);
                    $tituloLegible = str_replace(['_', '-'], ' ', $nombreSinExt);

                    $presentaciones[] = $this->formatearPresentacion(
                        $file,
                        $tema,
                        $tituloLegible,
                        $letraDisco,
                        "Pediatria/MaterialDocente/PowerPoint/{$tema}/{$nombreArchivo}"
                    );
                }
            }
        }

        // Temas sugeridos por defecto si la lista es corta
        $temasSugeridos = ['General', 'Neonatología', 'Urgencias Pediátricas', 'Infectología', 'Neumología', 'Guardia'];
        foreach ($temasSugeridos as $sug) {
            $temasEncontrados[$sug] = true;
        }

        $todosLosTemas = array_values(array_keys($temasEncontrados));
        sort($todosLosTemas, SORT_NATURAL | SORT_FLAG_CASE);

        // Aplicar filtros
        if (! empty($filtroTema)) {
            $presentaciones = array_filter($presentaciones, function ($item) use ($filtroTema) {
                return strcasecmp($item['tema'], $filtroTema) === 0;
            });
        }

        if (! empty($filtroBuscar)) {
            $presentaciones = array_filter($presentaciones, function ($item) use ($filtroBuscar) {
                $enTitulo = mb_stripos($item['titulo'], $filtroBuscar) !== false;
                $enArchivo = mb_stripos($item['archivo'], $filtroBuscar) !== false;
                $enTema = mb_stripos($item['tema'], $filtroBuscar) !== false;

                return $enTitulo || $enArchivo || $enTema;
            });
        }

        // Ordenar presentaciones alfabéticamente por título
        usort($presentaciones, function ($a, $b) {
            return strcasecmp($a['titulo'], $b['titulo']);
        });

        $rutaMostrar = str_replace('/', '\\', $rutaPrincipal);

        return response()->json([
            'success' => true,
            'presentaciones' => array_values($presentaciones),
            'total' => count($presentaciones),
            'temas' => $todosLosTemas,
            'ruta_base' => $rutaMostrar,
            'disco' => $this->documentoController->extraerLetraDisco($rutaPrincipal),
        ]);
    }

    /**
     * Formatea los datos de un archivo de presentación para el frontend.
     *
     * @return array{id: string, archivo: string, titulo: string, tema: string, extension: string, tamano: string, fecha: string, fecha_formateada: string, disco: string, ruta_relativa: string, url_descarga: string}
     */
    protected function formatearPresentacion(\SplFileInfo $file, string $tema, string $titulo, string $disco, string $rutaRelativa): array
    {
        $bytes = $file->getSize();
        $tamano = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : number_format($bytes / 1024, 0).' KB';

        $mtime = $file->getMTime();
        $fecha = date('Y-m-d H:i', $mtime);
        $fechaFormateada = date('d/m/Y H:i', $mtime);

        return [
            'id' => md5($file->getRealPath()),
            'archivo' => $file->getFilename(),
            'titulo' => $titulo,
            'tema' => $tema,
            'extension' => strtolower($file->getExtension()),
            'tamano' => $tamano,
            'fecha' => $fecha,
            'fecha_formateada' => $fechaFormateada,
            'disco' => $disco,
            'ruta_relativa' => $rutaRelativa,
            'url_descarga' => route('material.powerpoint.descargar', [
                'tema' => $tema,
                'archivo' => $file->getFilename(),
                'disco' => $disco,
            ]),
        ];
    }

    /**
     * Sube un nuevo archivo de PowerPoint asignándolo a un tema/categoría.
     */
    public function subir(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|max:153600', // 150 MB
            'tema' => 'nullable|string|max:80',
            'titulo' => 'nullable|string|max:150',
            'disco' => 'nullable|string|max:2',
        ]);

        $file = $request->file('archivo');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo no se ha subido correctamente.',
            ], 422);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $this->extensionesValidas, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Formato no permitido. Suba un archivo PowerPoint (.pptx, .ppt, .ppsx, etc.).',
            ], 422);
        }

        $temaInput = trim((string) $request->input('tema', ''));
        if (empty($temaInput)) {
            $temaInput = 'General';
        }

        $temaSanitizado = $this->sanitizarNombre($temaInput);
        if (empty($temaSanitizado)) {
            $temaSanitizado = 'General';
        }

        $discoInput = (string) $request->input('disco', '');
        $rutaBase = $this->documentoController->getRutaBasePediatria($discoInput);
        $carpetaDestino = "{$rutaBase}/MaterialDocente/PowerPoint/{$temaSanitizado}";

        File::ensureDirectoryExists($carpetaDestino, 0755, true);

        // Nombre de archivo seguro
        $tituloCustom = trim((string) $request->input('titulo', ''));
        if (! empty($tituloCustom)) {
            $nombreBase = $this->sanitizarNombre($tituloCustom);
        } else {
            $nombreOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $nombreBase = $this->sanitizarNombre($nombreOriginal);
        }

        if (empty($nombreBase)) {
            $nombreBase = 'Presentacion_'.now()->format('Ymd_His');
        }

        $nombreFinal = "{$nombreBase}.{$extension}";
        $rutaCompleta = "{$carpetaDestino}/{$nombreFinal}";

        // Si ya existe, agregar sufijo numérico o timestamp
        if (File::exists($rutaCompleta)) {
            $nombreFinal = "{$nombreBase}_".now()->format('His').".{$extension}";
            $rutaCompleta = "{$carpetaDestino}/{$nombreFinal}";
        }

        $file->move($carpetaDestino, $nombreFinal);

        $rutaReal = str_replace('/', '\\', realpath($rutaCompleta) ?: $rutaCompleta);

        return response()->json([
            'success' => true,
            'message' => '¡Presentación de PowerPoint subida con éxito!',
            'archivo' => [
                'nombre' => $nombreFinal,
                'tema' => $temaInput,
                'ruta_completa' => $rutaReal,
                'disco' => $this->documentoController->extraerLetraDisco($rutaReal),
            ],
        ]);
    }

    /**
     * Abre una presentación directamente en Microsoft PowerPoint en Windows.
     */
    public function abrir(Request $request): JsonResponse
    {
        $tema = basename(trim((string) $request->input('tema', '')));
        $archivo = basename(trim((string) $request->input('archivo', '')));
        $disco = (string) $request->input('disco', '');

        if (empty($archivo)) {
            return response()->json([
                'success' => false,
                'message' => 'Nombre de archivo no especificado.',
            ], 422);
        }

        $rutas = $this->obtenerRutasBase($disco);
        $candidatos = [];

        foreach ($rutas as $base) {
            if (! empty($tema) && $tema !== 'General') {
                $candidatos[] = "{$base}/{$tema}/{$archivo}";
            }
            $candidatos[] = "{$base}/{$archivo}";
            $candidatos[] = "{$base}/General/{$archivo}";
        }

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
                'message' => "La presentación '{$archivo}' no fue encontrada en el disco.",
            ], 404);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            @pclose(@popen("cmd.exe /c start \"\" \"{$rutaReal}\"", 'r'));
        }

        return response()->json([
            'success' => true,
            'message' => "Abriendo '{$archivo}' en Microsoft PowerPoint...",
            'ruta' => $rutaReal,
        ]);
    }

    /**
     * Abre la carpeta de PowerPoint en el Explorador de Windows.
     */
    public function abrirCarpeta(Request $request): JsonResponse
    {
        $tema = basename(trim((string) $request->input('tema', '')));
        $disco = (string) $request->input('disco', '');

        $rutas = $this->obtenerRutasBase($disco);
        $candidatos = [];

        foreach ($rutas as $base) {
            if (! empty($tema) && $tema !== 'General') {
                $candidatos[] = "{$base}/{$tema}";
            }
            $candidatos[] = $base;
        }

        $rutaReal = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $rutaReal = str_replace('/', '\\', realpath($cand) ?: $cand);
                break;
            }
        }

        if (! $rutaReal) {
            // Asegurar creación en la ruta principal si no existe
            $rutaPrincipal = $this->documentoController->getRutaBasePediatria($disco).'/MaterialDocente/PowerPoint';
            if (! empty($tema) && $tema !== 'General') {
                $rutaPrincipal .= "/{$tema}";
            }
            File::ensureDirectoryExists($rutaPrincipal, 0755, true);
            $rutaReal = str_replace('/', '\\', realpath($rutaPrincipal) ?: $rutaPrincipal);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            @pclose(@popen("cmd.exe /c start explorer.exe \"{$rutaReal}\"", 'r'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Carpeta abierta en el Explorador de Windows.',
            'ruta' => $rutaReal,
        ]);
    }

    /**
     * Descarga una presentación de PowerPoint.
     */
    public function descargar(Request $request, string $tema, string $archivo): BinaryFileResponse
    {
        $temaSeguro = basename($tema);
        $archivoSeguro = basename($archivo);
        $disco = (string) $request->query('disco', '');

        $rutas = $this->obtenerRutasBase($disco);
        $candidatos = [];

        foreach ($rutas as $base) {
            if (! empty($temaSeguro) && $temaSeguro !== 'General') {
                $candidatos[] = "{$base}/{$temaSeguro}/{$archivoSeguro}";
            }
            $candidatos[] = "{$base}/{$archivoSeguro}";
            $candidatos[] = "{$base}/General/{$archivoSeguro}";
        }

        $rutaReal = null;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                $rutaReal = $cand;
                break;
            }
        }

        if (! $rutaReal || ! File::exists($rutaReal)) {
            abort(404, 'La presentación no fue encontrada.');
        }

        return response()->download($rutaReal, $archivoSeguro);
    }

    /**
     * Elimina una presentación de PowerPoint del disco.
     */
    public function eliminar(Request $request): JsonResponse
    {
        $tema = basename(trim((string) $request->input('tema', '')));
        $archivo = basename(trim((string) $request->input('archivo', '')));
        $disco = (string) $request->input('disco', '');

        if (empty($archivo)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no especificado.',
            ], 422);
        }

        $rutas = $this->obtenerRutasBase($disco);
        $candidatos = [];

        foreach ($rutas as $base) {
            if (! empty($tema) && $tema !== 'General') {
                $candidatos[] = "{$base}/{$tema}/{$archivo}";
            }
            $candidatos[] = "{$base}/{$archivo}";
            $candidatos[] = "{$base}/General/{$archivo}";
        }

        $borrado = false;
        foreach ($candidatos as $cand) {
            if (File::exists($cand)) {
                File::delete($cand);
                $borrado = true;
                break;
            }
        }

        if (! $borrado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el archivo a eliminar.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "La presentación '{$archivo}' fue eliminada correctamente.",
        ]);
    }
}
