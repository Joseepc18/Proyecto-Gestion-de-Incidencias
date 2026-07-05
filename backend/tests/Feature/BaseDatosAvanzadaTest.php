<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Ciudad;
use App\Models\Evidencia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Pruebas de la capa avanzada de PostgreSQL: triggers, procedimientos, índices y vistas.
class BaseDatosAvanzadaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Trigger fn_registrar_cambio_estado: registra la transición en historial_estados,
    // atribuyéndola a quien EJECUTA el cambio y no al dueño.
    public function test_cambiar_estado_registra_el_historial(): void
    {
        $dueno = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($dueno);
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertOk();

        // El autor del cambio es el admin que lo ejecutó...
        $this->assertDatabaseHas('historial_estados', [
            'id_incidencia' => $incidencia->id_incidencia,
            'estado_anterior' => 'PENDIENTE',
            'estado_nuevo' => 'EN_PROCESO',
            'id_usuario' => $admin->id,
        ]);
        // ...no el dueño de la incidencia.
        $this->assertDatabaseMissing('historial_estados', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $dueno->id,
        ]);
    }

    // Procedimiento resolver_incidencia + trigger fn_fecha_resolucion: setea la fecha y notifica.
    public function test_resolver_incidencia_setea_fecha_y_notifica(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'RESUELTO',
        ])->assertOk();

        $incidencia->refresh();
        $this->assertNotNull($incidencia->fecha_resolucion);

        // El listener notifica la resolución al reportador.
        $this->assertNotificado($reportador, 'CAMBIO_ESTADO');

        // Al resolver vía procedimiento, el historial también atribuye al admin.
        $this->assertDatabaseHas('historial_estados', [
            'id_incidencia' => $incidencia->id_incidencia,
            'estado_nuevo' => 'RESUELTO',
            'id_usuario' => $admin->id,
        ]);
    }

    // Trigger fn_limite_evidencias: máximo 3 evidencias de tipo REPORTE.
    public function test_trigger_limita_evidencias_de_reporte_a_tres(): void
    {
        $usuario = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($usuario);

        for ($i = 1; $i <= 3; $i++) {
            Evidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'url_evidencia' => "incidencias/foto{$i}.jpg",
                'id_usuario' => $usuario->id,
                'tipo_evidencia' => 'REPORTE',
            ]);
        }

        $this->expectException(QueryException::class);

        // La 4.ª de REPORTE: el trigger cancela el INSERT.
        Evidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto4.jpg',
            'id_usuario' => $usuario->id,
            'tipo_evidencia' => 'REPORTE',
        ]);
    }

    // Índice único parcial: solo puede haber un RESPONSABLE por incidencia.
    public function test_no_permite_dos_responsables_en_una_incidencia(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));

        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $this->crearUsuario('tecnico')->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);

        $this->expectException(QueryException::class);

        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $this->crearUsuario('tecnico')->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
    }

    // Vista v_metricas_por_ubicacion: agrupa los conteos por ciudad y solo lista las
    // ciudades que tienen al menos una incidencia.
    public function test_vista_metricas_por_ubicacion_agrupa_por_ciudad(): void
    {
        $usuario = $this->crearUsuario('normal');
        $ciudades = Ciudad::take(2)->pluck('id_ciudad');
        [$ciudadA, $ciudadB] = [$ciudades[0], $ciudades[1]];

        // Dos incidencias en la ciudad A y una en la B.
        $this->crearIncidencia($usuario, ['id_ciudad' => $ciudadA]);
        $this->crearIncidencia($usuario, ['id_ciudad' => $ciudadA]);
        $this->crearIncidencia($usuario, ['id_ciudad' => $ciudadB]);

        $filaA = DB::table('v_metricas_por_ubicacion')->where('id_ciudad', $ciudadA)->first();
        $filaB = DB::table('v_metricas_por_ubicacion')->where('id_ciudad', $ciudadB)->first();

        $this->assertSame(2, (int) $filaA->total);
        $this->assertSame(2, (int) $filaA->total_pendientes);
        $this->assertSame(1, (int) $filaB->total);

        // Una ciudad sin incidencias no aparece en la vista.
        $ciudadVacia = Ciudad::whereNotIn('id_ciudad', [$ciudadA, $ciudadB])->value('id_ciudad');
        $this->assertNull(
            DB::table('v_metricas_por_ubicacion')->where('id_ciudad', $ciudadVacia)->first()
        );
    }
}
