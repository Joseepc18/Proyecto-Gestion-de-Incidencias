<?php

namespace Tests\Feature;

use App\Models\Evidencia;
use App\Models\Incidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AutoPurgaPapeleraTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_purga_incidencia_en_papelera_hace_mas_de_30_dias(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->delete();
        // Antigüedad en la papelera por encima del umbral: le toca la purga rodante.
        $incidencia->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

        Artisan::call('model:prune', ['--model' => [Incidencia::class]]);

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
    }

    public function test_conserva_incidencia_en_papelera_hace_menos_de_30_dias(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->delete();
        $incidencia->forceFill(['deleted_at' => now()->subDays(29)])->saveQuietly();

        Artisan::call('model:prune', ['--model' => [Incidencia::class]]);

        // Sigue en la papelera (soft-deleted), no se borró definitivamente.
        $this->assertNotNull(Incidencia::onlyTrashed()->find($incidencia->id_incidencia));
    }

    public function test_al_purgar_borra_los_archivos_fisicos_de_las_evidencias(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);
        $incidencia->delete();
        $incidencia->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

        Artisan::call('model:prune', ['--model' => [Incidencia::class]]);

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
        Storage::disk('evidencias')->assertMissing('incidencias/foto.jpg');
    }
}
