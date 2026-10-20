<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaterialDocenteTest extends TestCase
{
    public function test_listar_powerpoint_retorna_estructura_json(): void
    {
        $response = $this->getJson(route('material.powerpoint.listar'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'presentaciones',
            'total',
            'temas',
            'ruta_base',
            'disco',
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_subir_powerpoint_valida_archivo(): void
    {
        $response = $this->postJson(route('material.powerpoint.subir'), []);

        $response->assertStatus(422);
    }

    public function test_subir_powerpoint_guarda_archivo_correctamente(): void
    {
        $archivoFake = UploadedFile::fake()->create('Reanimacion_Neonatal_Test.pptx', 100);

        $response = $this->postJson(route('material.powerpoint.subir'), [
            'archivo' => $archivoFake,
            'tema' => 'Neonatologia_Test',
            'titulo' => 'Reanimacion Neonatal Test',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'archivo' => [
                'nombre',
                'tema',
                'ruta_completa',
                'disco',
            ],
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $rutaCompleta = $data['archivo']['ruta_completa'];
        $this->assertFileExists($rutaCompleta);

        // Limpieza del archivo de test
        if (File::exists($rutaCompleta)) {
            File::delete($rutaCompleta);
            $carpeta = dirname($rutaCompleta);
            if (File::exists($carpeta) && count(File::files($carpeta)) === 0) {
                File::deleteDirectory($carpeta);
            }
        }
    }

    public function test_abrir_powerpoint_inexistente_retorna_404(): void
    {
        $response = $this->postJson(route('material.powerpoint.abrir'), [
            'tema' => 'General',
            'archivo' => 'no_existe_archivo_xyz.pptx',
        ]);

        $response->assertStatus(404);
        $this->assertFalse($response->json('success'));
    }

    public function test_abrir_carpeta_retorna_exito(): void
    {
        $response = $this->postJson(route('material.powerpoint.abrir-carpeta'), [
            'tema' => 'General',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('ruta', $response->json());
    }
}
