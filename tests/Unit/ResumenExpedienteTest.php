<?php

namespace Tests\Unit;

use App\Http\Controllers\DocumentoController;
use ReflectionMethod;
use Tests\TestCase;

class ResumenExpedienteTest extends TestCase
{
    public function test_arma_el_resumen_con_lo_escrito_en_cada_archivo(): void
    {
        $historia = "Motivo de Ingreso: Dificultad respiratoria y fiebre.\nHistoria de la Enfermedad Actual: Cuadro de cuatro días.\nDiagnósticos Clínicos Concluyentes:1. BRONQUITIS AGUDA\n2. DESHIDRATACIÓN LEVE\nPlan de manejo: suero";
        $indicaciones = "Nombre Paciente: ANA RIOS ........ Edad: 6 A ........ Peso: 22.8 kg ...... Kg:.......... Fecha: 08/10/26\nHora\nINDICACIONES MÉDICAS\n07:00\nDieta blanda\nMetamizol 400 mg cada 12 horas\nCuidados por enfermería\nR1 PEREZ\nINFORME A LOS PADRES: ..........";
        $admision = "Edad / Sexo:\n3 AÑOS / FEMENINO\nTiempo de evolución y antecedentes previos: Cuadro de 10 horas con fatiga y disnea.\n3. DIAGNÓSTICOS DE INGRESO\n1. CRISIS ASMÁTICA\n2. NEUMONÍA\n4. PLAN DE INVESTIGACIÓN / EXÁMENES\nRégimen Dietético: Dieta blanda para la edad.\nCuidados de Enfermería:";

        $resumen = $this->resumen($historia, $indicaciones, $admision);

        $this->assertSame('Dificultad respiratoria y fiebre', $this->campo($resumen[0], 'Motivo de ingreso'));
        $this->assertSame("1. BRONQUITIS AGUDA\n2. DESHIDRATACIÓN LEVE", $this->campo($resumen[0], 'Diagnósticos'));
        $this->assertSame('6 A', $this->campo($resumen[1], 'Edad'));
        $this->assertSame('22.8 kg', $this->campo($resumen[1], 'Peso'));
        $this->assertSame("Dieta blanda\nMetamizol 400 mg cada 12 horas\nCuidados por enfermería", $this->campo($resumen[1], 'Órdenes'));
        $this->assertSame('3 AÑOS / FEMENINO', $this->campo($resumen[2], 'Edad / sexo'));
        $this->assertSame('Cuadro de 10 horas con fatiga y disnea', $this->campo($resumen[2], 'Motivo'));
        $this->assertSame("1. CRISIS ASMÁTICA\n2. NEUMONÍA", $this->campo($resumen[2], 'Diagnósticos'));
        $this->assertSame('Dieta blanda para la edad', $this->campo($resumen[2], 'Dieta'));
    }

    public function test_marca_el_archivo_ausente_y_deja_vacio_el_que_no_tiene_datos(): void
    {
        $resumen = $this->resumen('Sin etiquetas clínicas.', "Hora\nINDICACIONES MÉDICAS", null);

        $this->assertTrue($resumen[0]['presente']);
        $this->assertSame([], $resumen[0]['campos']);
        $this->assertTrue($resumen[1]['presente']);
        $this->assertSame([], $resumen[1]['campos']);
        $this->assertFalse($resumen[2]['presente']);
    }

    public function test_si_la_via_esta_en_blanco_indicaciones_solo_muestra_edad_y_peso(): void
    {
        $filas = [
            ['Hora', 'INDICACIONES MÉDICAS', 'Vía', 'Horario'],
            ['07:00', 'Dieta blanda', '', ''],
            ['', 'Metamizol 400 mg', '', ''],
        ];

        $resumen = $this->resumen(null, 'Edad: 6 A ........ Peso: 22.8 kg ...... Kg:', null, $filas);

        $this->assertSame('6 A', $this->campo($resumen[1], 'Edad'));
        $this->assertSame('22.8 kg', $this->campo($resumen[1], 'Peso'));
        $this->assertNull($this->campo($resumen[1], 'Órdenes'));
    }

    public function test_muestra_la_fila_cuando_la_via_esta_escrita(): void
    {
        $filas = [
            ['Hora', 'INDICACIONES MÉDICAS', 'Vía'],
            ['07:00', 'Metamizol 400 mg cada 12 horas', 'EV'],
            ['08:00', 'Dieta blanda', ''],
        ];

        $resumen = $this->resumen(null, 'Edad: 4 A Peso: 16 kg', null, $filas);

        $this->assertSame('Metamizol 400 mg cada 12 horas · EV', $this->campo($resumen[1], 'Órdenes'));
    }

    /**
     * @param  list<list<string>>  $filas
     * @return list<array{titulo: string, presente: bool, campos: list<array{etiqueta: string, texto: string}>}>
     */
    private function resumen(?string $historia, ?string $indicaciones, ?string $admision, array $filas = []): array
    {
        $metodo = new ReflectionMethod(DocumentoController::class, 'armarResumenExpediente');

        return $metodo->invoke(app(DocumentoController::class), $historia, $indicaciones, $admision, $filas);
    }

    /**
     * @param  array{campos: list<array{etiqueta: string, texto: string}>}  $bloque
     */
    private function campo(array $bloque, string $etiqueta): ?string
    {
        foreach ($bloque['campos'] as $campo) {
            if ($campo['etiqueta'] === $etiqueta) {
                return $campo['texto'];
            }
        }

        return null;
    }
}
