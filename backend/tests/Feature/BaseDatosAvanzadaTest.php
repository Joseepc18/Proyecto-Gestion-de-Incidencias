<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Comentario;
use App\Models\Evidencia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Pruebas de la capa avanzada de PostgreSQL: triggers, procedimientos e índices.
class BaseDatosAvanzadaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Trigger fn_registrar_cambio_estado: registra la transición en historial_estados,
    // atribuyéndola a quien EJECUTA el cambio y no al dueño (H-02).
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

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $reportador->id,
            'id_incidencia' => $incidencia->id_incidencia,
            'tipo_notificacion' => 'CAMBIO_ESTADO',
        ]);

        // Al resolver vía procedimiento, el historial también atribuye al admin (H-02).
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

    // Trigger fn_notificar_nuevo_comentario: notifica al reportador si comenta otro.
    public function test_nuevo_comentario_notifica_al_reportador(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);

        Comentario::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $this->crearUsuario('tecnico')->id,
            'comentario' => 'Estamos revisando tu reporte.',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $reportador->id,
            'id_incidencia' => $incidencia->id_incidencia,
            'tipo_notificacion' => 'COMENTARIO',
        ]);
    }
}
