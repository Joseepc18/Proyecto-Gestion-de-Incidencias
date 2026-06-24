<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\SubtipoIncidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackendExtraTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_crear_incidencia_sin_prioridad_entra_como_media(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $datos = $this->datosIncidenciaValidos();
        unset($datos['prioridad_incidencia']);

        $this->postJson('/api/incidencias', $datos)->assertCreated();

        $this->assertDatabaseHas('incidencias', [
            'nombre_incidencia' => $datos['nombre_incidencia'],
            'prioridad_incidencia' => 'MEDIA',
        ]);
    }

    public function test_descripcion_respeta_el_maximo(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'descripcion_incidencia' => str_repeat('a', 501),
        ]))->assertStatus(422)->assertJsonValidationErrors('descripcion_incidencia');
    }

    public function test_solo_el_autor_edita_su_comentario(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $comentario = Comentario::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $autor->id,
            'comentario' => 'Comentario original.',
        ]);

        // El autor sí puede editarlo.
        Sanctum::actingAs($autor);
        $this->putJson("/api/comentarios/{$comentario->id_comentario}", [
            'comentario' => 'Comentario corregido.',
        ])->assertOk();

        // Otro usuario no.
        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->putJson("/api/comentarios/{$comentario->id_comentario}", [
            'comentario' => 'Intento ajeno.',
        ])->assertStatus(403);
    }

    public function test_usuario_edita_su_propio_perfil(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->putJson('/api/perfil', [
            'name' => 'Nombre Actualizado',
            'email' => $usuario->email,
        ])->assertOk()->assertJsonFragment(['name' => 'Nombre Actualizado']);
    }

    public function test_admin_crea_tipo_y_no_puede_borrarlo_con_subtipos(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        // Crear un tipo.
        $resp = $this->postJson('/api/tipos-incidencia', [
            'nombre_tipo_incidencia' => 'Alumbrado público',
        ])->assertCreated();
        $idTipo = $resp->json('id_tipo_incidencia');

        // Con un subtipo asociado, no se puede eliminar.
        SubtipoIncidencia::create([
            'nombre_subtipo_incidencia' => 'Poste apagado',
            'id_tipo_incidencia' => $idTipo,
        ]);
        $this->deleteJson("/api/tipos-incidencia/{$idTipo}")->assertStatus(422);

        $this->assertDatabaseHas('tipos_incidencia', ['id_tipo_incidencia' => $idTipo]);
    }

    public function test_no_admin_no_puede_crear_tipos(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->postJson('/api/tipos-incidencia', [
            'nombre_tipo_incidencia' => 'Intento sin permiso',
        ])->assertStatus(403);

        // Evita un tipo huérfano si por error se llegara a crear.
        $this->assertDatabaseMissing('tipos_incidencia', ['nombre_tipo_incidencia' => 'Intento sin permiso']);
    }
}
