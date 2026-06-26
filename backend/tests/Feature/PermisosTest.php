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
}
