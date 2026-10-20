<?php

namespace Tests\Feature;

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
}
