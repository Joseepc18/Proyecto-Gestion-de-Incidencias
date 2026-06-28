<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Ciudad;
use App\Models\Comentario;
use App\Models\SubtipoIncidencia;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Mockery;
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

        // La prioridad ya no es obligatoria (entra como MEDIA por defecto).
        $this->postJson('/api/incidencias', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'nombre_incidencia',
                'latitud_incidencia',
                'longitud_incidencia',
                'id_ciudad',
                'id_subtipo_incidencia',
            ])
            ->assertJsonMissingValidationErrors('prioridad_incidencia');
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

    public function test_admin_puede_fijar_estado_al_crear(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'EN_PROCESO',
        ]))->assertCreated();

        $this->assertDatabaseHas('incidencias', [
            'nombre_incidencia' => 'Bache peligroso en la avenida principal',
            'estado_incidencia' => 'EN_PROCESO',
        ]);
    }

    public function test_ciudadano_no_puede_fijar_estado_al_crear(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        // Aunque mande estado RESUELTO, se ignora y entra como PENDIENTE.
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'RESUELTO',
        ]))->assertCreated();

        $this->assertDatabaseHas('incidencias', [
            'nombre_incidencia' => 'Bache peligroso en la avenida principal',
            'estado_incidencia' => 'PENDIENTE',
        ]);
    }

    public function test_si_la_foto_no_se_guarda_no_crea_evidencia_fantasma(): void
    {
        // Forzamos que store() falle (como en prod por permisos): putFileAs devuelve false.
        $disco = Mockery::mock(Filesystem::class);
        $disco->shouldReceive('putFileAs')->andReturn(false);
        $fabrica = Mockery::mock(FilesystemFactory::class);
        $fabrica->shouldReceive('disk')->andReturn($disco);
        $this->app->instance(FilesystemFactory::class, $fabrica);

        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->post('/api/incidencias', $this->datosIncidenciaValidos([
            'fotos' => [UploadedFile::fake()->image('foto.jpg')],
        ]), ['Accept' => 'application/json'])->assertStatus(500);

        // No queda incidencia ni evidencia a medias y se registró el error de ARCHIVO.
        $this->assertDatabaseMissing('evidencias', ['url_evidencia' => '0']);
        $this->assertDatabaseMissing('incidencias', ['nombre_incidencia' => 'Bache peligroso en la avenida principal']);
        $this->assertDatabaseHas('bitacora_errores', ['tipo_error' => 'ARCHIVO']);
    }

    public function test_titulo_respeta_longitud_minima_y_maxima(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        // Muy corto (menos de 5 caracteres).
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos(['nombre_incidencia' => 'abc']))
            ->assertStatus(422)->assertJsonValidationErrors('nombre_incidencia');

        // Muy largo (más de 100 caracteres).
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos(['nombre_incidencia' => str_repeat('a', 101)]))
            ->assertStatus(422)->assertJsonValidationErrors('nombre_incidencia');
    }

    public function test_listado_aplica_los_filtros_de_busqueda(): void
    {
        $autor = $this->crearUsuario('normal');

        // Dos ciudades y dos tipos distintos para ejercitar los filtros por id.
        [$ciudadA, $ciudadB] = Ciudad::take(2)->pluck('id_ciudad');
        $subtipoA = SubtipoIncidencia::first();
        $subtipoB = SubtipoIncidencia::where('id_tipo_incidencia', '!=', $subtipoA->id_tipo_incidencia)->first();

        $this->crearIncidencia($autor, [
            'nombre_incidencia' => 'Semaforo dañado en el centro',
            'estado_incidencia' => 'PENDIENTE',
            'prioridad_incidencia' => 'ALTA',
            'id_ciudad' => $ciudadA,
            'id_subtipo_incidencia' => $subtipoA->id_subtipo_incidencia,
        ]);
        $this->crearIncidencia($autor, [
            'nombre_incidencia' => 'Fuga de agua en la avenida',
            'estado_incidencia' => 'RESUELTO',
            'prioridad_incidencia' => 'BAJA',
            'id_ciudad' => $ciudadB,
            'id_subtipo_incidencia' => $subtipoB->id_subtipo_incidencia,
        ]);

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->getJson('/api/incidencias?estado=PENDIENTE')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.nombre_incidencia', 'Semaforo dañado en el centro');

        $this->getJson('/api/incidencias?prioridad=BAJA')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.prioridad_incidencia', 'BAJA');

        // La búsqueda es ilike: parcial y sin distinguir mayúsculas.
        $this->getJson('/api/incidencias?busqueda=fuga')
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.nombre_incidencia', 'Fuga de agua en la avenida');

        $this->getJson("/api/incidencias?ciudad_id={$ciudadA}")
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id_ciudad', $ciudadA);

        $this->getJson("/api/incidencias?tipo_id={$subtipoB->id_tipo_incidencia}")
            ->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.nombre_incidencia', 'Fuga de agua en la avenida');
    }

    public function test_tecnico_no_puede_saltar_estados_no_permitidos(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        Sanctum::actingAs($responsable);

        // Desde PENDIENTE el técnico solo puede ir a EN_PROCESO; saltar a RESUELTO se rechaza.
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'RESUELTO'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Transición de estado no permitida']);

        // La incidencia sigue PENDIENTE (no se aplicó el cambio).
        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'estado_incidencia' => 'PENDIENTE',
        ]);
    }
}
