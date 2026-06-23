<?php

namespace Tests\Feature;

use App\Models\Comentario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IncidenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_usuario_autenticado_crea_incidencia(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $respuesta = $this->postJson('/api/incidencias', $this->datosIncidenciaValidos());

        $respuesta->assertCreated();

        // Queda guardada y asociada a quien la reportó.
        $this->assertDatabaseHas('incidencias', [
            'nombre_incidencia' => 'Bache peligroso en la avenida principal',
            'id_usuario' => $usuario->id,
        ]);
    }

    public function test_crear_incidencia_sin_datos_requeridos_devuelve_422(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/incidencias', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'nombre_incidencia',
                'latitud_incidencia',
                'longitud_incidencia',
                'prioridad_incidencia',
                'id_ciudad',
                'id_subtipo_incidencia',
            ]);
    }

    public function test_eliminar_incidencia_con_comentarios_e_historial(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);

        // Le agregamos hijos: un cambio de estado (historial) y un comentario.
        $incidencia->update(['estado_incidencia' => 'EN_PROCESO']);
        Comentario::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $autor->id,
            'comentario' => 'Comentario de prueba.',
        ]);

        Sanctum::actingAs($autor);

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")->assertOk();

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
    }

    public function test_titulo_respeta_longitud_minima_y_maxima(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        // Muy corto (menos de 5 caracteres).
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos(['nombre_incidencia' => 'abc']))
            ->assertStatus(422)->assertJsonValidationErrors('nombre_incidencia');

        // Muy largo (más de 255 caracteres).
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos(['nombre_incidencia' => str_repeat('a', 256)]))
            ->assertStatus(422)->assertJsonValidationErrors('nombre_incidencia');
    }
}
