<?php

namespace Tests\Feature;

use Tests\TestCase;

class AsignacionTest extends TestCase
{
    public function test_asignacion_exige_un_tipo_valido(): void
    {
        $response = $this->postJson(route('documentos.asignacion'), [
            'fecha' => '2026-11-10',
            'paciente' => 'Paciente_Prueba',
            'disco' => 'D',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tipo']);
        $response->assertJsonPath('errors.tipo.0', 'Elige si entra estable, en cuidado intermedio o crítico.');
    }

    public function test_asignacion_rechaza_un_tipo_desconocido(): void
    {
        $response = $this->postJson(route('documentos.asignacion'), [
            'fecha' => '2026-11-10',
            'paciente' => 'Paciente_Prueba',
            'disco' => 'D',
            'tipo' => 'grave',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.tipo.0', 'El tipo tiene que ser estable, intermedio o crítico.');
    }

    public function test_asignacion_responde_cuando_el_paciente_no_existe(): void
    {
        $response = $this->postJson(route('documentos.asignacion'), [
            'fecha' => '2020-01-01',
            'paciente' => 'Paciente_Inexistente_Asignacion',
            'disco' => 'D',
            'tipo' => 'estable',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    }

    public function test_asignacion_acepta_cuidado_intermedio_cuando_el_paciente_no_existe(): void
    {
        $response = $this->postJson(route('documentos.asignacion'), [
            'fecha' => '2020-01-01',
            'paciente' => 'Paciente_Inexistente_Asignacion',
            'disco' => 'D',
            'tipo' => 'intermedio',
            'medico' => 'Guardia A',
            'notas' => 'Control cada hora.',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    }

    public function test_asignacion_rechaza_notas_demasiado_largas(): void
    {
        $response = $this->postJson(route('documentos.asignacion'), [
            'fecha' => '2026-11-10',
            'paciente' => 'Paciente_Prueba',
            'tipo' => 'estable',
            'notas' => str_repeat('a', 2001),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notas']);
    }
}
