<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Evidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $otro = $this->crearUsuario('normal');
        Sanctum::actingAs($otro);

        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertStatus(403);
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
        $otro = $this->crearUsuario('normal');
        Sanctum::actingAs($otro);

        // Debe primar el 403 de autorización sobre el 422 de validación: la Policy
        // corre antes que las reglas porque vive en el authorize() del FormRequest.
        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'ab',
        ])->assertStatus(403);
    }

    // H-E: el autor ciudadano puede editar su PENDIENTE, pero no auto-asignarse prioridad.
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
        // El nombre sí cambió...
        $this->assertSame('Bache con prioridad inflada', $incidencia->nombre_incidencia);
        // ...pero la prioridad se ignoró: sigue en MEDIA.
        $this->assertSame('MEDIA', $incidencia->prioridad_incidencia);
    }

    // H-E: el admin sí puede cambiar la prioridad de una incidencia.
    public function test_admin_si_puede_cambiar_la_prioridad(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'prioridad_incidencia' => 'ALTA',
        ])->assertOk();

        $this->assertSame('ALTA', $incidencia->fresh()->prioridad_incidencia);
    }

    public function test_no_admin_no_accede_a_la_gestion_de_usuarios(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->getJson('/api/usuarios')->assertStatus(403);
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

    // El técnico no puede ver el detalle de una incidencia donde no está asignado.
    public function test_tecnico_no_puede_ver_detalle_de_incidencia_no_asignada(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $tecnico = $this->crearUsuario('tecnico');

        Sanctum::actingAs($tecnico);

        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertStatus(403);
    }

    // El técnico de apoyo sí puede ver el detalle de la incidencia donde está asignado.
    public function test_tecnico_apoyo_puede_ver_detalle_de_incidencia_asignada(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $apoyo = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $apoyo->id,
            'rol_asignado' => 'APOYO',
        ]);

        Sanctum::actingAs($apoyo);

        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}")->assertOk();
    }

    // El autor no puede eliminar si la incidencia ya pasó de PENDIENTE (pérdida de trazabilidad).
    public function test_autor_no_puede_eliminar_incidencia_en_proceso(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $incidencia->update(['estado_incidencia' => 'EN_PROCESO']);
        Sanctum::actingAs($autor);

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertStatus(403);
    }

    public function test_autor_no_puede_eliminar_incidencia_resuelta(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $incidencia->update(['estado_incidencia' => 'RESUELTO']);
        Sanctum::actingAs($autor);

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")
            ->assertStatus(403);
    }

    // El admin puede eliminar sin importar el estado.
    public function test_admin_puede_eliminar_incidencia_resuelta(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->update(['estado_incidencia' => 'RESUELTO']);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}", ['motivo' => 'Duplicada.'])
            ->assertOk();
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

    // El técnico responsable solo puede cerrar EN_PROCESO→RESUELTO; el admin arranca el trabajo.
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
            ->assertJson(['message' => 'Transición de estado no permitida']);
    }

    // El admin sí puede pasar PENDIENTE→EN_PROCESO (arrancar el trabajo).
    public function test_admin_puede_iniciar_incidencia_pendiente_a_en_proceso(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertOk();

        $this->assertSame('EN_PROCESO', $incidencia->fresh()->estado_incidencia);
    }

    // Única excepción a "RESUELTO es terminal": el admin puede reabrir a EN_PROCESO
    // (limpia fecha_resolucion vía trigger, ya que el update pasa por el flujo normal).
    public function test_admin_puede_reabrir_incidencia_resuelta(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        $responsable = $this->crearUsuario('tecnico');
        $apoyo = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $apoyo->id,
            'rol_asignado' => 'APOYO',
        ]);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertOk();

        $fresca = $incidencia->fresh();
        $this->assertSame('EN_PROCESO', $fresca->estado_incidencia);
        $this->assertNull($fresca->fecha_resolucion);

        // Responsable y apoyo ven la misma alerta (SOLICITUD_REAPERTURA) que la del admin,
        // no el aviso azul genérico de CAMBIO_ESTADO.
        foreach ([$responsable, $apoyo] as $tecnico) {
            $this->assertDatabaseHas('notificaciones', [
                'id_usuario' => $tecnico->id,
                'id_incidencia' => $incidencia->id_incidencia,
                'tipo_notificacion' => 'SOLICITUD_REAPERTURA',
            ]);
        }
    }

    // El admin no puede saltar de RESUELTO a PENDIENTE (solo la reapertura a EN_PROCESO tiene sentido).
    public function test_admin_no_puede_pasar_resuelto_a_pendiente(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'PENDIENTE',
        ])->assertStatus(422);
    }

    // El técnico responsable NUNCA puede reabrir, ni siquiera la suya resuelta.
    public function test_tecnico_no_puede_reabrir_incidencia_resuelta(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        Sanctum::actingAs($responsable);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertStatus(422);
    }

    // En RESUELTO nadie sube evidencias: el expediente queda cerrado. Si hace falta, el
    // reportador pide reapertura y un admin reabre a EN_PROCESO.
    public function test_responsable_no_puede_subir_evidencia_a_resuelta(): void
    {
        Storage::fake('public');
        $responsable = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);

        Sanctum::actingAs($responsable);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/evidencias", [
            'fotos' => [UploadedFile::fake()->image('r.jpg')],
            'tipo_evidencia' => 'RESOLUCION',
        ])->assertStatus(403);
    }

    // En RESUELTO tampoco se borran evidencias (expediente cerrado, la reapertura es la excepción).
    public function test_responsable_no_puede_borrar_evidencia_de_resuelta(): void
    {
        Storage::fake('public');
        $responsable = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'), ['estado_incidencia' => 'EN_PROCESO']);
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);

        // Sube una foto de resolución mientras está EN_PROCESO (permitido).
        Sanctum::actingAs($responsable);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/evidencias", [
            'fotos' => [UploadedFile::fake()->image('r.jpg')],
            'tipo_evidencia' => 'RESOLUCION',
        ])->assertOk();
        $evidencia = Evidencia::first();

        // Se resuelve y el responsable intenta borrarla: 403 (expediente cerrado).
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        $this->deleteJson("/api/evidencias/{$evidencia->id_evidencia}")->assertStatus(403);
    }

    // En RESUELTO el chat queda en solo lectura: no se pueden crear comentarios.
    public function test_no_se_puede_comentar_incidencia_resuelta(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);

        Sanctum::actingAs($reportador);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios", [
            'comentario' => 'Pero esto sigue roto.',
        ])->assertStatus(403);

        // El historial del chat (Lectura) sigue siendo accesible.
        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios")->assertOk();
    }

    // Solo 1 solicitud de reapertura pendiente a la vez: una segunda se rechaza (422)
    // mientras la primera esté sin leer. Si el admin la lee, se libera el cupo.
    public function test_no_se_puede_pedir_reapertura_doble_si_hay_una_pendiente(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);
        $this->crearUsuario('admin');

        Sanctum::actingAs($reportador);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/solicitar-reapertura", [
            'motivo' => 'El hueco sigue igual, no lo taparon.',
        ])->assertOk();

        // Segundo intento while la primera sigue sin leer: 422.
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/solicitar-reapertura", [
            'motivo' => 'Otro motivo distinto para reintentar.',
        ])->assertStatus(422);
    }
}
