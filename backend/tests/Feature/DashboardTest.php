<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // El dashboard de métricas es solo para admin y trae los bloques esperados.
    public function test_dashboard_de_metricas_solo_admin(): void
    {
        $this->crearIncidencia($this->crearUsuario('normal'));

        // Un ciudadano no entra.
        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->getJson('/api/dashboard/metricas')->assertStatus(403);

        // El admin sí, con la estructura esperada.
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/dashboard/metricas')
            ->assertOk()
            ->assertJsonStructure([
                'totales',
                'promedio_dias',
                'por_prioridad' => ['alta', 'media', 'baja'],
                'por_tipo',
                'por_ubicacion',
                'por_provincia',
                'por_mes',
            ]);
    }

    // Blinda la regresión de #24: cacheado como Collection/stdClass no se rehidrataba y rompía el dashboard al volver.
    public function test_metricas_se_cachean_como_arrays_planos(): void
    {
        $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/dashboard/metricas')->assertOk();

        $cache = Cache::get('dashboard_metricas');
        $this->assertIsArray($cache['totales']);
        $this->assertIsArray($cache['por_prioridad']);
        $this->assertIsArray($cache['por_tipo']);
        $this->assertIsArray($cache['por_ubicacion']);
        $this->assertIsArray($cache['por_provincia']);
        $this->assertIsArray($cache['por_mes']);
    }

    // El dashboard del técnico es solo para técnicos y cuenta únicamente SUS asignaciones.
    public function test_dashboard_del_tecnico_solo_tecnico_y_cuenta_lo_suyo(): void
    {
        $reportador = $this->crearUsuario('normal');
        $tecnico = $this->crearUsuario('tecnico');

        // Responsable de una activa y de una resuelta este mes; apoyo en otra activa.
        AsignacionIncidencia::create([
            'id_incidencia' => $this->crearIncidencia($reportador)->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        $resuelta = $this->crearIncidencia($reportador);
        $resuelta->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        AsignacionIncidencia::create([
            'id_incidencia' => $resuelta->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        AsignacionIncidencia::create([
            'id_incidencia' => $this->crearIncidencia($reportador)->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'APOYO',
        ]);
        // Incidencia ajena que NO debe aparecer en sus números.
        $this->crearIncidencia($reportador);

        // Ni el ciudadano ni el admin entran.
        Sanctum::actingAs($reportador);
        $this->getJson('/api/dashboard/tecnico')->assertStatus(403);
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/dashboard/tecnico')->assertStatus(403);

        Sanctum::actingAs($tecnico);
        $respuesta = $this->getJson('/api/dashboard/tecnico')
            ->assertOk()
            ->assertJsonStructure(['totales', 'por_semana', 'activas'])
            ->json();

        $this->assertEquals(1, $respuesta['totales']['pendientes']);
        $this->assertEquals(1, $respuesta['totales']['resueltas_mes']);
        $this->assertEquals(1, $respuesta['totales']['apoyo_activas']);
        // Activas: la suya como responsable y la de apoyo; la ajena no entra.
        $this->assertCount(2, $respuesta['activas']);
        $this->assertCount(8, $respuesta['por_semana']);
    }
}
