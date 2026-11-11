<?php

namespace Tests\Unit;

use App\Http\Controllers\DocumentoController;
use ReflectionMethod;
use Tests\TestCase;

class DiagnosticoHistoriaTest extends TestCase
{
    public function test_lee_el_diagnostico_escrito_despues_de_la_etiqueta_clinica(): void
    {
        $texto = "7. DIAGNÓSTICOS DE INGRESO Y CONDUCTA TERAPÉUTICA\nDiagnósticos Clínicos Concluyentes:1. BRONQUITIS AGUDA (CIE-10: J20)2. DESHIDRATACIÓN LEVE";

        $diagnostico = $this->leer($texto);

        $this->assertSame("1. BRONQUITIS AGUDA (CIE-10: J20)\n2. DESHIDRATACIÓN LEVE", $diagnostico);
    }

    public function test_junta_los_diagnosticos_numerados_de_las_lineas_siguientes(): void
    {
        $texto = "Diagnósticos Clínicos Concluyentes:1. BRONQUITIS AGUDA (CIE-10: J20)\n2. DESHIDRATACIÓN LEVE\nPlan de manejo: suero";

        $diagnostico = $this->leer($texto);

        $this->assertSame("1. BRONQUITIS AGUDA (CIE-10: J20)\n2. DESHIDRATACIÓN LEVE", $diagnostico);
    }

    public function test_ignora_la_linea_de_puntos_y_usa_el_diagnostico_escrito(): void
    {
        $diagnostico = $this->leer("Diagnóstico de ingreso: ........\nDiagnóstico: Fiebre de origen desconocido");

        $this->assertSame('Fiebre de origen desconocido', $diagnostico);
    }

    public function test_devuelve_vacio_cuando_la_historia_no_trae_diagnostico(): void
    {
        $this->assertNull($this->leer('Motivo de consulta: tos de tres días'));
    }

    private function leer(string $texto): ?string
    {
        $metodo = new ReflectionMethod(DocumentoController::class, 'leerDiagnostico');

        return $metodo->invoke(app(DocumentoController::class), $texto);
    }
}
