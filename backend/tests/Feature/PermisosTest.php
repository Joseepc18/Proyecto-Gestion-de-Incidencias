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
