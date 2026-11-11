<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\MaterialDocenteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (DocumentoController $controller) {
    $discos = $controller->obtenerDiscosDisponibles();
    $discoPredeterminado = $controller->obtenerDiscoPredeterminado();
    $discoActivoObj = collect($discos)->firstWhere('disco', $discoPredeterminado) ?? ($discos[0] ?? null);
    $plantillasInfo = $controller->obtenerEstadoPlantillas($discoPredeterminado);

    return view('welcome', compact('discos', 'discoPredeterminado', 'discoActivoObj', 'plantillasInfo'));
});

Route::prefix('documentos')->group(function () {
    Route::post('/ingresar-paciente', [DocumentoController::class, 'ingresarPaciente'])->name('documentos.ingresar-paciente');
    Route::get('/historial', [DocumentoController::class, 'historial'])->name('documentos.historial');
    Route::post('/abrir-carpeta', [DocumentoController::class, 'abrirCarpeta'])->name('documentos.abrir-carpeta');
    Route::post('/abrir-archivo', [DocumentoController::class, 'abrirArchivo'])->name('documentos.abrir-archivo');
    Route::get('/descargar-archivo/{fecha}/{paciente}/{archivo}', [DocumentoController::class, 'descargarArchivo'])->name('documentos.descargar-archivo');
    Route::get('/descargar-zip/{fecha}/{paciente}', [DocumentoController::class, 'descargarZip'])->name('documentos.descargar-zip');
    Route::get('/descargar-plantilla/{tipo}', [DocumentoController::class, 'descargarPlantilla'])->name('documentos.descargar-plantilla');
    Route::post('/abrir-plantilla', [DocumentoController::class, 'abrirPlantilla'])->name('documentos.abrir-plantilla');
    Route::post('/subir-plantilla', [DocumentoController::class, 'subirPlantilla'])->name('documentos.subir-plantilla');
    Route::get('/plantillas-estado', [DocumentoController::class, 'estadoPlantillasJson'])->name('documentos.plantillas-estado');
    Route::get('/drive-status', [DocumentoController::class, 'driveStatus'])->name('documentos.drive-status');
    Route::post('/extraer-texto-paciente', [DocumentoController::class, 'extraerTextoPaciente'])->name('documentos.extraer-texto-paciente');
});

Route::prefix('material-docente')->group(function () {
    Route::get('/powerpoint', [MaterialDocenteController::class, 'listar'])->name('material.powerpoint.listar');
    Route::post('/powerpoint/subir', [MaterialDocenteController::class, 'subir'])->name('material.powerpoint.subir');
    Route::post('/powerpoint/crear-tema', [MaterialDocenteController::class, 'crearTema'])->name('material.powerpoint.crear-tema');
    Route::post('/powerpoint/abrir', [MaterialDocenteController::class, 'abrir'])->name('material.powerpoint.abrir');
    Route::post('/powerpoint/abrir-carpeta', [MaterialDocenteController::class, 'abrirCarpeta'])->name('material.powerpoint.abrir-carpeta');
    Route::get('/powerpoint/descargar/{tema}/{archivo}', [MaterialDocenteController::class, 'descargar'])->name('material.powerpoint.descargar');
    Route::delete('/powerpoint/eliminar', [MaterialDocenteController::class, 'eliminar'])->name('material.powerpoint.eliminar');
});
