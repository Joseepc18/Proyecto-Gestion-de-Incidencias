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

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")->assertOk();
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
}
