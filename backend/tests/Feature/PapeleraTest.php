<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Comentario;
use App\Models\Evidencia;
use App\Models\HistorialEstado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PapeleraTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_eliminar_incidencia_es_soft_delete_y_retiene_hijos(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Comentario::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        HistorialEstado::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        AsignacionIncidencia::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        Evidencia::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);

        Sanctum::actingAs($autor);

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")->assertOk();

        $this->assertSoftDeleted('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseHas('comentarios', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseHas('historial_estados', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseHas('asignaciones_incidencia', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseHas('evidencias', ['id_incidencia' => $incidencia->id_incidencia]);

        // Ya en la papelera: desaparece del listado normal.
        $this->getJson('/api/incidencias/'.$incidencia->id_incidencia)->assertStatus(404);
    }

    // Solo se elimina en PENDIENTE: ni el admin (con permiso) puede borrar una En proceso o Cerrada.
    public function test_solo_se_elimina_en_estado_pendiente(): void
    {
        $autor = $this->crearUsuario('normal');
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $enProceso = $this->crearIncidencia($autor, ['estado_incidencia' => 'EN_PROCESO']);
        $this->deleteJson("/api/incidencias/{$enProceso->id_incidencia}", ['motivo' => 'Motivo de prueba'])
            ->assertStatus(403);

        $cerrada = $this->crearIncidencia($autor, ['estado_incidencia' => 'CERRADO']);
        $this->deleteJson("/api/incidencias/{$cerrada->id_incidencia}", ['motivo' => 'Motivo de prueba'])
            ->assertStatus(403);

        $pendiente = $this->crearIncidencia($autor, ['estado_incidencia' => 'PENDIENTE']);
        $this->deleteJson("/api/incidencias/{$pendiente->id_incidencia}", ['motivo' => 'Motivo de prueba'])
            ->assertOk();
        $this->assertSoftDeleted('incidencias', ['id_incidencia' => $pendiente->id_incidencia]);
    }

    // super_admin es view-only y ya no tiene incidencias.papelera por defecto (ver PermisosNuevosTest).
    public function test_solo_quien_tiene_incidencias_papelera_ve_la_papelera(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->delete();

        Sanctum::actingAs($this->crearUsuario('tecnico'));
        $this->getJson('/api/incidencias/papelera')->assertStatus(403);

        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/incidencias/papelera')
            ->assertOk()
            ->assertJsonFragment(['id_incidencia' => $incidencia->id_incidencia])
            // El contador del front lee el plazo de retención desde la respuesta, sin hardcodear el 30.
            ->assertJsonPath('data.0.dias_retencion_papelera', 30);
    }

    public function test_restaurar_incidencia_la_devuelve_al_listado_activo(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->delete();

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/restaurar")->assertOk();

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'deleted_at' => null,
        ]);
        $this->getJson('/api/incidencias/'.$incidencia->id_incidencia)->assertOk();
    }

    public function test_purgar_incidencia_borra_hijos_notificaciones_y_archivo(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Comentario::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        HistorialEstado::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        AsignacionIncidencia::factory()->create(['id_incidencia' => $incidencia->id_incidencia]);
        Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);
        $incidencia->delete();

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}/purgar")->assertOk();

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseMissing('comentarios', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseMissing('historial_estados', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseMissing('asignaciones_incidencia', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseMissing('evidencias', ['id_incidencia' => $incidencia->id_incidencia]);
        Storage::disk('evidencias')->assertMissing('incidencias/foto.jpg');
    }

    public function test_purgar_solo_aplica_a_incidencias_en_papelera(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}/purgar")->assertStatus(404);
    }

    public function test_dashboard_metricas_no_cuenta_incidencias_en_papelera(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin = $this->crearUsuario('admin');

        Sanctum::actingAs($admin);
        $this->getJson('/api/dashboard/metricas')->assertJsonPath('totales.total', 1);

        $incidencia->delete();
        Cache::forget('dashboard_metricas');

        $this->getJson('/api/dashboard/metricas')->assertJsonPath('totales.total', 0);
    }
}
