<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\SubtipoIncidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackendExtraTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

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

    public function test_usuario_sube_su_foto_de_perfil(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->put('/api/perfil', [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'foto' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertOk();

        $usuario->refresh();
        $this->assertNotNull($usuario->foto_perfil);
        Storage::disk('public')->assertExists($usuario->foto_perfil);
    }

    public function test_super_admin_crea_tipo_y_no_puede_borrarlo_con_subtipos(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        // Crear un tipo.
        $resp = $this->postJson('/api/tipos-incidencia', [
            'nombre_tipo_incidencia' => 'Alumbrado público',
        ])->assertCreated();
        $idTipo = $resp->json('id_tipo_incidencia');

        // Con un subtipo asociado, no se puede eliminar (guarda de integridad).
        SubtipoIncidencia::create([
            'nombre_subtipo_incidencia' => 'Poste apagado',
            'id_tipo_incidencia' => $idTipo,
        ]);
        $this->deleteJson("/api/tipos-incidencia/{$idTipo}")->assertStatus(422);

        $this->assertDatabaseHas('tipos_incidencia', ['id_tipo_incidencia' => $idTipo]);
    }
}
