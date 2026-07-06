<?php

namespace Tests\Feature;

use App\Models\Evidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EvidenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_la_url_firmada_de_la_evidencia_sirve_el_archivo(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $evidencia = Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);

        // La URL firmada la genera el accessor (como en la respuesta ya autorizada de la incidencia).
        $this->get($evidencia->url_completa)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_sin_firma_valida_la_ruta_del_archivo_rechaza(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $evidencia = Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);

        // Sin los parámetros expires/signature el middleware signed responde 403.
        $this->get('/api/evidencias/'.$evidencia->id_evidencia.'/archivo')
            ->assertForbidden();
    }

    public function test_un_usuario_ajeno_no_puede_subir_evidencias_a_una_incidencia(): void
    {
        Storage::fake('evidencias');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));

        // Un ciudadano que ni reportó ni es responsable no debe poder subir fotos a la incidencia.
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->postJson('/api/incidencias/'.$incidencia->id_incidencia.'/evidencias', [
            'fotos' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertForbidden();
    }

    public function test_un_usuario_ajeno_no_puede_eliminar_una_evidencia(): void
    {
        Storage::fake('evidencias');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $evidencia = Evidencia::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);

        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->deleteJson('/api/evidencias/'.$evidencia->id_evidencia)->assertForbidden();
        $this->assertDatabaseHas('evidencias', ['id_evidencia' => $evidencia->id_evidencia]);
    }

    public function test_no_se_puede_superar_el_limite_de_evidencias_por_tipo(): void
    {
        Storage::fake('evidencias');
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($autor);

        $ruta = '/api/incidencias/'.$incidencia->id_incidencia.'/evidencias';

        // Las 3 primeras fotos de REPORTE entran; la 4ª debe rechazarse con 422 desde el controller (no 500 del trigger).
        $this->postJson($ruta, [
            'fotos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ])->assertOk();

        $this->postJson($ruta, ['fotos' => [UploadedFile::fake()->image('d.jpg')]])
            ->assertStatus(422);

        $this->assertCount(3, $incidencia->evidencias()->where('tipo_evidencia', 'REPORTE')->get());
    }
}
