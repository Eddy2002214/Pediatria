<?php

namespace Tests\Feature;

use App\Http\Controllers\DocumentoController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentoTest extends TestCase
{
    public function test_pagina_principal_carga_correctamente_con_discos(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Disco');
        $response->assertSee('Pediatria');
    }

    public function test_ingresar_paciente_crea_carpeta_y_archivos(): void
    {
        $nombrePaciente = 'Test_Paciente_'.time();

        $response = $this->postJson(route('documentos.ingresar-paciente'), [
            'nombre' => $nombrePaciente,
            'disco' => 'D',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'paciente',
            'paciente_carpeta',
            'fecha',
            'disco',
            'ruta_completa',
            'ruta_relativa',
            'archivos',
            'zip_url',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('D', $data['disco']);
        $this->assertDirectoryExists($data['ruta_completa']);

        if (File::exists($data['ruta_completa'])) {
            File::deleteDirectory($data['ruta_completa']);
        }

        $driveTestFolder = 'G:/Mi unidad/PEDIATRIA/'.now()->format('Y-m-d').'/'.$nombrePaciente;
        if (File::exists($driveTestFolder)) {
            File::deleteDirectory($driveTestFolder);
        }
    }

    public function test_historial_retorna_lista_de_pacientes(): void
    {
        $response = $this->getJson(route('documentos.historial'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'pacientes',
            'total',
            'fechas_disponibles',
            'disco_predeterminado',
        ]);
    }

    public function test_abrir_carpeta_retorna_json(): void
    {
        $response = $this->postJson(route('documentos.abrir-carpeta'), [
            'fecha' => '2026-10-19',
            'paciente' => 'inexistente_123',
            'disco' => 'D',
        ]);

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_abrir_archivo_retorna_json(): void
    {
        $response = $this->postJson(route('documentos.abrir-archivo'), [
            'fecha' => '2026-10-19',
            'paciente' => 'inexistente_123',
            'archivo' => 'Historia_Clinica.docx',
            'disco' => 'D',
        ]);

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_historial_ignora_carpetas_que_no_sean_fechas(): void
    {
        $response = $this->getJson(route('documentos.historial'));

        $response->assertStatus(200);
        $pacientes = $response->json('pacientes');
        $fechasDisponibles = $response->json('fechas_disponibles');

        $nombresPacientes = array_column($pacientes, 'nombre');
        $this->assertNotContains('PowerPoint', $nombresPacientes);
        $this->assertNotContains('MaterialDocente', $fechasDisponibles);

        foreach ($fechasDisponibles as $fecha) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $fecha);
        }
    }

    public function test_consultar_estado_plantillas_retorna_informacion_valida(): void
    {
        $response = $this->getJson(route('documentos.plantillas-estado'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'plantillas' => [
                'historia-clinica' => ['tipo', 'titulo', 'archivo_oficial', 'archivo_real', 'origen', 'existe'],
                'indicaciones' => ['tipo', 'titulo', 'archivo_oficial', 'archivo_real', 'origen', 'existe'],
                'admision' => ['tipo', 'titulo', 'archivo_oficial', 'archivo_real', 'origen', 'existe'],
            ],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_abrir_plantilla_valida_tipo_incorrecto(): void
    {
        $response = $this->postJson(route('documentos.abrir-plantilla'), [
            'tipo' => 'tipo_invalido_xyz',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
    }

    public function test_descargar_plantilla_descarga_archivo_docx(): void
    {
        $response = $this->get(route('documentos.descargar-plantilla', ['tipo' => 'admision']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    public function test_subir_plantilla_renombra_archivo_correctamente(): void
    {
        $mappingPath = storage_path('app/plantillas_mapping.json');
        $originalMapping = File::exists($mappingPath) ? File::get($mappingPath) : null;
        $localTemplatePath = base_path('Plantillas/Admision.docx');
        $originalContent = File::exists($localTemplatePath) ? File::get($localTemplatePath) : null;

        try {
            $contenidoReal = File::get($localTemplatePath);
            $file = UploadedFile::fake()->createWithContent(
                'Nota_Admision_Pediatria_Optimizada_v2.docx',
                $contenidoReal ?: 'PK'
            );

            $response = $this->post(route('documentos.subir-plantilla'), [
                'tipo' => 'admision',
                'archivo' => $file,
                'disco' => 'D',
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
                'archivo' => 'Nota_Admision_Pediatria_Optimizada_v2.docx',
            ]);

            $estado = $response->json('estado');
            $this->assertEquals('Nota_Admision_Pediatria_Optimizada_v2.docx', $estado['admision']['archivo_real']);
        } finally {
            if ($originalMapping !== null) {
                File::put($mappingPath, $originalMapping);
            }
            if ($originalContent !== null) {
                File::put($localTemplatePath, $originalContent);
            }
        }
    }

    public function test_abrir_archivo_con_coincidencia_inteligente_y_auto_reparacion(): void
    {
        $testDir = base_path('Pediatria/2026-11-10/paciente_test_autoheal');
        File::ensureDirectoryExists($testDir, 0755, true);

        try {
            $response = $this->postJson(route('documentos.abrir-archivo'), [
                'fecha' => '2026-11-10',
                'paciente' => 'paciente_test_autoheal',
                'archivo' => 'Admision.docx',
                'ruta' => $testDir,
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'success' => true,
            ]);
            $this->assertFileExists("{$testDir}/Admision.docx");
        } finally {
            File::deleteDirectory(base_path('Pediatria/2026-11-10'));
        }
    }

    public function test_paciente_de_la_copia_local_pasa_a_la_carpeta_compartida(): void
    {
        $raiz = storage_path('framework/testing/drive-compartido');
        $id = '1KPUHbk11USXJRu5ZkQwt-o5G7Pz8lb1X';
        $compartida = $raiz.'/.shortcut-targets-by-id/'.$id.'/PEDIATRIA';
        $copiaLocal = $raiz.'/Mi unidad/PEDIATRIA';
        $expediente = $copiaLocal.'/2026-11-11/mario_hugo/Historia_Clinica.docx';

        File::deleteDirectory($raiz);
        File::ensureDirectoryExists($compartida.'/PLANTILLAS', 0755, true);
        File::put($compartida.'/PLANTILLAS/plantilla.docx', 'plantilla-real');
        File::ensureDirectoryExists(dirname($expediente), 0755, true);
        File::put($expediente, 'expediente-mario');
        File::ensureDirectoryExists($copiaLocal.'/MaterialDocente/PowerPoint', 0755, true);
        File::put($raiz.'/Mi unidad/PEDIATRIA.lnk', 'shortcut-targets-by-id '.$id);

        try {
            $destino = app(DocumentoController::class)->consolidarPacientesDrive($raiz);

            $this->assertSame(str_replace('\\', '/', $compartida), $destino);
            $this->assertFileExists($compartida.'/2026-11-11/mario_hugo/Historia_Clinica.docx');
            $this->assertSame('expediente-mario', File::get($compartida.'/2026-11-11/mario_hugo/Historia_Clinica.docx'));
            $this->assertFileExists($compartida.'/PLANTILLAS/plantilla.docx');
            $this->assertDirectoryDoesNotExist($copiaLocal);
            $this->assertDirectoryDoesNotExist($compartida.'/MaterialDocente');
        } finally {
            File::deleteDirectory($raiz);
        }
    }
}
