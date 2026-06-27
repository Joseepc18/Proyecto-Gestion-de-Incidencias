<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Evidencia;
use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\TipoIncidencia;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

// Reglas de acceso de los técnicos (apoyo vs responsable) y cobertura de
// endpoints que faltaban: asignación, límite de evidencias y dashboard.
class RolesYCoberturaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // El técnico de APOYO solo puede VER el detalle: ni chat, ni estado, ni evidencias, ni editar.
    public function test_apoyo_solo_puede_ver_el_detalle(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $apoyo = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $apoyo->id,
            'rol_asignado' => 'APOYO',
        ]);
        Sanctum::actingAs($apoyo);

        $id = $incidencia->id_incidencia;

        // Ver el detalle: permitido.
        $this->getJson("/api/incidencias/{$id}")->assertOk();

        // Todo lo demás: prohibido (403).
        $this->getJson("/api/incidencias/{$id}/comentarios")->assertStatus(403);
        $this->patchJson("/api/incidencias/{$id}/estado", ['estado_incidencia' => 'EN_PROCESO'])->assertStatus(403);
        $this->postJson("/api/incidencias/{$id}/evidencias", [])->assertStatus(403);
        $this->putJson("/api/incidencias/{$id}", ['nombre_incidencia' => 'Intento del apoyo'])->assertStatus(403);
    }

    // El técnico RESPONSABLE cambia el estado y usa el chat, pero NO edita los detalles.
    public function test_responsable_cambia_estado_pero_no_edita_detalles(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador);
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        Sanctum::actingAs($responsable);

        $id = $incidencia->id_incidencia;

        // Puede ver el chat y avanzar el estado.
        $this->getJson("/api/incidencias/{$id}/comentarios")->assertOk();
        $this->patchJson("/api/incidencias/{$id}/estado", ['estado_incidencia' => 'EN_PROCESO'])->assertOk();

        // Pero NO puede editar los detalles de la incidencia.
        $this->putJson("/api/incidencias/{$id}", ['nombre_incidencia' => 'Detalle cambiado por el técnico'])
            ->assertStatus(403);
    }

    // El admin asigna un técnico vía el endpoint (procedimiento asignar_tecnico).
    public function test_admin_asigna_tecnico_responsable(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $tecnico = $this->crearUsuario('tecnico');
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/asignaciones", [
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ])->assertCreated();

        $this->assertDatabaseHas('asignaciones_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
    }

    // El controller rechaza pasar del límite de fotos por tipo (antes de tocar la BD).
    public function test_no_pasa_del_limite_de_evidencias_por_tipo(): void
    {
        Storage::fake('public');
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($autor);

        // 4 fotos de REPORTE de una vez: el tope es 3, debe rechazar con 422.
        $this->post("/api/incidencias/{$incidencia->id_incidencia}/evidencias", [
            'fotos' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
                UploadedFile::fake()->image('4.jpg'),
            ],
            'tipo_evidencia' => 'REPORTE',
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    // Si store() falla (como en prod por permisos), no se crea evidencia fantasma y se avisa el error.
    public function test_subir_evidencia_falla_si_no_se_guarda_la_foto(): void
    {
        $disco = Mockery::mock(Filesystem::class);
        $disco->shouldReceive('putFileAs')->andReturn(false);
        $fabrica = Mockery::mock(FilesystemFactory::class);
        $fabrica->shouldReceive('disk')->andReturn($disco);
        $this->app->instance(FilesystemFactory::class, $fabrica);

        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($autor);

        $this->post("/api/incidencias/{$incidencia->id_incidencia}/evidencias", [
            'fotos' => [UploadedFile::fake()->image('foto.jpg')],
            'tipo_evidencia' => 'REPORTE',
        ], ['Accept' => 'application/json'])->assertStatus(500);

        $this->assertDatabaseMissing('evidencias', ['url_evidencia' => '0']);
        $this->assertDatabaseHas('bitacora_errores', ['tipo_error' => 'ARCHIVO']);
    }

    // El dashboard de métricas es solo para admin y trae los tres bloques.
    public function test_dashboard_de_metricas_solo_admin(): void
    {
        $this->crearIncidencia($this->crearUsuario('normal'));

        // Un ciudadano no entra.
        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->getJson('/api/dashboard/metricas')->assertStatus(403);

        // El admin sí, con la estructura esperada.
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/dashboard/metricas')
            ->assertOk()
            ->assertJsonStructure([
                'totales',
                'promedio_dias',
                'por_prioridad' => ['alta', 'media', 'baja'],
                'por_tipo',
                'por_ubicacion',
                'por_provincia',
                'por_mes',
            ]);
    }

    // El técnico RESPONSABLE puede borrar una evidencia (reemplazar su foto).
    public function test_responsable_puede_borrar_evidencia(): void
    {
        Storage::fake('public');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        $evidencia = Evidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/resolucion.jpg',
            'id_usuario' => $responsable->id,
            'tipo_evidencia' => 'RESOLUCION',
        ]);

        Sanctum::actingAs($responsable);
        $this->deleteJson("/api/evidencias/{$evidencia->id_evidencia}")->assertOk();
        $this->assertDatabaseMissing('evidencias', ['id_evidencia' => $evidencia->id_evidencia]);
    }

    // El ciudadano siempre crea en MEDIA: aunque mande prioridad ALTA, se ignora.
    public function test_ciudadano_no_fija_prioridad_al_crear(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'prioridad_incidencia' => 'ALTA',
        ]))->assertCreated()->assertJsonFragment(['prioridad_incidencia' => 'MEDIA']);
    }

    // Tras subir el límite, el tipo RESOLUCION admite hasta 3 fotos (la 4.ª la corta el trigger).
    public function test_resolucion_permite_hasta_tres_evidencias(): void
    {
        $usuario = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($usuario);

        for ($i = 1; $i <= 3; $i++) {
            Evidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'url_evidencia' => "incidencias/res{$i}.jpg",
                'id_usuario' => $usuario->id,
                'tipo_evidencia' => 'RESOLUCION',
            ]);
        }

        $this->expectException(QueryException::class);
        Evidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/res4.jpg',
            'id_usuario' => $usuario->id,
            'tipo_evidencia' => 'RESOLUCION',
        ]);
    }

    // El admin quita una asignación existente.
    public function test_admin_quita_asignacion(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $asignacion = AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $this->crearUsuario('tecnico')->id,
            'rol_asignado' => 'APOYO',
        ]);

        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->deleteJson("/api/asignaciones/{$asignacion->id_asignacion}")->assertOk();
        $this->assertDatabaseMissing('asignaciones_incidencia', ['id_asignacion' => $asignacion->id_asignacion]);
    }

    // El admin edita un tipo y un subtipo de incidencia.
    public function test_admin_edita_tipo_y_subtipo(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        $tipo = TipoIncidencia::create(['nombre_tipo_incidencia' => 'Vialidad']);
        $this->putJson("/api/tipos-incidencia/{$tipo->id_tipo_incidencia}", [
            'nombre_tipo_incidencia' => 'Vialidad y tránsito',
        ])->assertOk();
        $this->assertDatabaseHas('tipos_incidencia', ['nombre_tipo_incidencia' => 'Vialidad y tránsito']);

        $subtipo = $tipo->subtipos()->create(['nombre_subtipo_incidencia' => 'Semáforo']);
        $this->putJson("/api/subtipos-incidencia/{$subtipo->id_subtipo_incidencia}", [
            'nombre_subtipo_incidencia' => 'Semáforo dañado',
            'id_tipo_incidencia' => $tipo->id_tipo_incidencia,
        ])->assertOk();
        $this->assertDatabaseHas('subtipos_incidencia', ['nombre_subtipo_incidencia' => 'Semáforo dañado']);
    }

    // El usuario marca una notificación suya como leída.
    public function test_marcar_notificacion_como_leida(): void
    {
        $usuario = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($usuario);
        $notificacion = Notificacion::create([
            'id_usuario' => $usuario->id,
            'id_incidencia' => $incidencia->id_incidencia,
            'tipo_notificacion' => 'CAMBIO_ESTADO',
            'mensaje_notificacion' => 'Tu incidencia cambió de estado.',
        ]);

        Sanctum::actingAs($usuario);
        $this->patchJson("/api/notificaciones/{$notificacion->id_notificacion}/leida")->assertOk();
        $this->assertDatabaseHas('notificaciones', [
            'id_notificacion' => $notificacion->id_notificacion,
            'estado_lectura' => true,
        ]);
    }

    // El admin actualiza y luego elimina (borrado lógico) a otro usuario.
    public function test_admin_actualiza_y_elimina_usuario(): void
    {
        $rolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');
        $objetivo = $this->crearUsuario('normal');
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->putJson("/api/usuarios/{$objetivo->id}", [
            'name' => 'Técnico Promovido',
            'email' => $objetivo->email,
            'id_rol' => $rolTecnico,
        ])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $objetivo->id, 'name' => 'Técnico Promovido']);

        $this->deleteJson("/api/usuarios/{$objetivo->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $objetivo->id]);
    }
}
