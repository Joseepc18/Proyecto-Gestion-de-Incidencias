<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_ciudadano_no_puede_ver_incidencia_ajena(): void
    {
        $dueno = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($dueno);

        // Otro ciudadano intenta ver la incidencia que no es suya.
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}")->assertStatus(403);
    }

    public function test_autor_edita_solo_si_esta_pendiente(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($autor);

        // PENDIENTE: el autor sí puede editar.
        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'Bache aún más grande que antes',
        ])->assertOk();

        // El admin la pasa a EN_PROCESO; ahora el autor ya no debe poder editar.
        $incidencia->update(['estado_incidencia' => 'EN_PROCESO']);

        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'Intento de edición ya en proceso',
        ])->assertStatus(403);
    }

    public function test_autorizacion_corre_antes_que_validacion(): void
    {
        $dueno = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($dueno);

        // Otro ciudadano (sin permiso) intenta editar con datos inválidos (título muy corto).
        Sanctum::actingAs($this->crearUsuario('normal'));

        // Debe primar el 403 de autorización sobre el 422 de validación: la Policy
        // corre antes que las reglas porque vive en el authorize() del FormRequest.
        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'ab',
        ])->assertStatus(403);
    }

    // El autor ciudadano puede editar su PENDIENTE, pero no auto-asignarse prioridad.
    public function test_ciudadano_no_puede_cambiar_la_prioridad(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor); // PENDIENTE, prioridad MEDIA
        Sanctum::actingAs($autor);

        // Edita un campo permitido y, de paso, intenta colar prioridad ALTA.
        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'Bache con prioridad inflada',
            'prioridad_incidencia' => 'ALTA',
        ])->assertOk();

        $incidencia->refresh();
        // El nombre sí cambió, pero la prioridad se ignoró: sigue en MEDIA.
        $this->assertSame('Bache con prioridad inflada', $incidencia->nombre_incidencia);
        $this->assertSame('MEDIA', $incidencia->prioridad_incidencia->value);
    }

    public function test_tecnico_solo_ve_incidencias_donde_esta_asignado(): void
    {
        $ciudadano = $this->crearUsuario('normal');
        $asignada = $this->crearIncidencia($ciudadano);
        $otra = $this->crearIncidencia($ciudadano);

        $tecnico = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $asignada->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);

        Sanctum::actingAs($tecnico);

        $this->getJson('/api/incidencias')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id_incidencia' => $asignada->id_incidencia])
            ->assertJsonMissing(['id_incidencia' => $otra->id_incidencia]);
    }

    // El admin borrando la incidencia de otro debe explicar el motivo (se le notifica al dueño).
    public function test_admin_no_puede_eliminar_sin_motivo(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');

        $this->assertDatabaseHas('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
    }

    // El técnico responsable solo puede cerrar EN_PROCESO→RESUELTO; arrancar el trabajo es del admin.
    // PENDIENTE→EN_PROCESO existe en el grafo, así que lo corta la guarda de ROL (no la estructural).
    public function test_tecnico_no_puede_iniciar_incidencia_pendiente_a_en_proceso(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        Sanctum::actingAs($responsable);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Tu rol no puede realizar ese cambio de estado']);
    }

    // Única excepción a "RESUELTO es terminal": el admin reabre a EN_PROCESO, y SOLO si el
    // reportador lo pidió antes (limpia fecha_resolucion, apaga la bandera y avisa a los técnicos).
    public function test_admin_puede_reabrir_incidencia_resuelta(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        $admin = $this->crearUsuario('admin');

        // El reportador pide la reapertura primero (si no, el admin no puede tocarla).
        Sanctum::actingAs($reportador);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/solicitar-reapertura", [
            'motivo' => 'El hueco sigue igual.',
        ])->assertOk();

        Sanctum::actingAs($admin);
        $respuesta = $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertOk()->json();

        $fresca = $incidencia->fresh();
        $this->assertSame('EN_PROCESO', $fresca->estado_incidencia->value);
        $this->assertNull($fresca->fecha_resolucion);
        // Ya no queda pendiente: se atendió la solicitud.
        $this->assertFalse($respuesta['reapertura_pendiente']);
        $this->assertNotificado($responsable, 'SOLICITUD_REAPERTURA');
    }

    // Solo 1 solicitud a la vez: mientras el admin no reabra, no se puede pedir otra (se valida
    // con la bandera, no con el estado de lectura de la notificación).
    public function test_no_se_puede_pedir_reapertura_doble_hasta_que_el_admin_reabra(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        $this->crearUsuario('admin');

        Sanctum::actingAs($reportador);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/solicitar-reapertura", [
            'motivo' => 'El hueco sigue igual, no lo taparon.',
        ])->assertOk();

        // El detalle sigue marcando la solicitud como pendiente (el botón "Reabrir" debe verse).
        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertOk()->assertJson(['reapertura_pendiente' => true]);

        // Y el reportador NO puede reenviar mientras no se reabra: 422.
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/solicitar-reapertura", [
            'motivo' => 'Otro motivo distinto para reintentar.',
        ])->assertStatus(422);
    }
}
