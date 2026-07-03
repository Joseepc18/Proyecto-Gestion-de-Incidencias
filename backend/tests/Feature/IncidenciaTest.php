<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Ciudad;
use App\Models\Comentario;
use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\SubtipoIncidencia;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
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

        // El admin puede eliminar en cualquier estado; aquí probamos el cascade de hijos.
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}", ['motivo' => 'Duplicada con la INC-1.'])
            ->assertOk();

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
    }

    // La notificación del motivo debe sobrevivir al borrado físico de la incidencia (id_incidencia null).
    public function test_eliminar_notifica_el_motivo_y_sobrevive_al_borrado(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}", ['motivo' => 'Reporte duplicado.'])
            ->assertOk();

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $autor->id,
            'id_incidencia' => null,
            'tipo_notificacion' => 'INCIDENCIA_ELIMINADA',
        ]);
        $notificacion = Notificacion::where('id_usuario', $autor->id)
            ->where('tipo_notificacion', 'INCIDENCIA_ELIMINADA')
            ->firstOrFail();
        $this->assertStringContainsString('Reporte duplicado.', $notificacion->mensaje_notificacion);
    }

    // El dueño borrando su propia incidencia (PENDIENTE) no necesita explicarse ni genera notificación.
    public function test_autor_elimina_su_propia_incidencia_sin_motivo(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        Sanctum::actingAs($autor);

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}")->assertOk();

        $this->assertDatabaseMissing('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);
        $this->assertDatabaseMissing('notificaciones', [
            'id_usuario' => $autor->id,
            'tipo_notificacion' => 'INCIDENCIA_ELIMINADA',
        ]);
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

    public function test_crear_en_en_proceso_registra_historial_inicial(): void
    {
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'EN_PROCESO',
        ]))->assertCreated();

        $incidencia = Incidencia::where('estado_incidencia', 'EN_PROCESO')->firstOrFail();

        // Nacer en EN_PROCESO deja la fila inicial PENDIENTE→EN_PROCESO atribuida al admin.
        $this->assertDatabaseHas('historial_estados', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $admin->id,
            'estado_anterior' => 'PENDIENTE',
            'estado_nuevo' => 'EN_PROCESO',
        ]);
    }

    public function test_crear_en_pendiente_no_registra_historial(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'PENDIENTE',
        ]))->assertCreated();

        // El estado por defecto no genera fila de historial (no hubo transición).
        $this->assertDatabaseCount('historial_estados', 0);
    }

    public function test_admin_no_puede_crear_incidencia_en_resuelto(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        // RESUELTO ya no es opción al crear: para resolver se pasa por cambiarEstado (corre el SP).
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'RESUELTO',
        ]))->assertStatus(422)->assertJsonValidationErrors('estado_incidencia');
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

        // El técnico responsable solo puede cerrar EN_PROCESO→RESUELTO; saltar a RESUELTO desde PENDIENTE se rechaza.
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'RESUELTO'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Transición de estado no permitida']);

        // La incidencia sigue PENDIENTE (no se aplicó el cambio).
        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'estado_incidencia' => 'PENDIENTE',
        ]);
    }

    public function test_no_se_puede_asignar_un_admin_como_tecnico(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $otroAdmin = $this->crearUsuario('admin');
        Sanctum::actingAs($this->crearUsuario('admin'));

        // Solo el rol 'tecnico' puede asignarse; un admin es rechazado en validación (422).
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/asignaciones", [
            'id_usuario' => $otroAdmin->id,
            'rol_asignado' => 'RESPONSABLE',
        ])->assertStatus(422)->assertJsonValidationErrors('id_usuario');

        $this->assertDatabaseMissing('asignaciones_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $otroAdmin->id,
        ]);
    }

    public function test_se_puede_asignar_un_tecnico(): void
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

    public function test_listado_manda_las_resueltas_al_final(): void
    {
        $autor = $this->crearUsuario('normal');

        // Resuelta más antigua y pendiente más nueva: aunque la pendiente sea más reciente
        // naturalmente, forzamos created_at para garantizar el orden de creación esperado.
        $resuelta = $this->crearIncidencia($autor, ['estado_incidencia' => 'RESUELTO']);
        $resuelta->created_at = now()->subDay();
        $resuelta->save();

        $pendiente = $this->crearIncidencia($autor, ['estado_incidencia' => 'PENDIENTE']);

        Sanctum::actingAs($this->crearUsuario('admin'));

        $respuesta = $this->getJson('/api/incidencias?per_page=10')->assertOk();

        // La pendiente (no resuelta) va primero; la resuelta queda al final mesmo siendo la más vieja.
        $this->assertSame($pendiente->id_incidencia, $respuesta->json('data.0.id_incidencia'));
        $this->assertSame($resuelta->id_incidencia, $respuesta->json('data.1.id_incidencia'));
    }

    // "Reclamar" v1: el primer admin que reclama queda como id_admin_atiende.
    public function test_admin_reclama_una_incidencia_sin_reclamar(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertOk()
            ->assertJsonPath('id_admin_atiende', $admin->id)
            ->assertJsonPath('admin_atiende.id', $admin->id);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => $admin->id,
        ]);
    }

    // Otro admin no puede reclamar una incidencia que ya tiene dueño.
    public function test_otro_admin_no_puede_reclamar_incidencia_ya_reclamada(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin1 = $this->crearUsuario('admin');
        $admin2 = $this->crearUsuario('admin');

        Sanctum::actingAs($admin1);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        Sanctum::actingAs($admin2);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertStatus(422)
            ->assertJson(['message' => 'Esta incidencia ya fue reclamada por otro administrador.']);

        // Sigue siendo del primero, no se pisó.
        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => $admin1->id,
        ]);
    }

    // Solo el admin dueño (id_admin_atiende) puede archivar, y solo si ya está RESUELTO.
    public function test_solo_el_admin_que_reclamo_puede_archivar_una_resuelta(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin1 = $this->crearUsuario('admin');
        $admin2 = $this->crearUsuario('admin');

        Sanctum::actingAs($admin1);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);

        // Otro admin (que no reclamó) no puede archivarla.
        Sanctum::actingAs($admin2);
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/archivar")->assertStatus(403);

        // El dueño sí puede, y fecha_resolucion se conserva (no se limpia como al reabrir).
        Sanctum::actingAs($admin1);
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/archivar")
            ->assertOk()
            ->assertJsonPath('estado_incidencia', 'CERRADO');

        $incidencia->refresh();
        $this->assertSame('CERRADO', $incidencia->estado_incidencia);
        $this->assertNotNull($incidencia->fecha_resolucion);
    }

    // No se puede archivar antes de que la incidencia esté RESUELTO (aunque ya la hayan reclamado).
    public function test_no_se_puede_archivar_una_incidencia_que_no_esta_resuelta(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/archivar")
            ->assertStatus(422)
            ->assertJson(['message' => 'Solo se pueden archivar incidencias resueltas.']);
    }

    // CERRADO es terminal: ni el admin puede volver a cambiarle el estado por la vía genérica.
    public function test_no_se_puede_cambiar_estado_de_una_incidencia_archivada(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->update(['estado_incidencia' => 'CERRADO']);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'EN_PROCESO'])
            ->assertStatus(422)
            ->assertJson(['message' => 'No se puede cambiar el estado de una incidencia archivada']);
    }

    // CERRADO tampoco es un destino válido de /estado: solo se llega por /archivar o el job automático.
    public function test_cambiar_estado_no_acepta_cerrado_como_destino(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now(), 'reapertura_solicitada' => true]);
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'CERRADO'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado_incidencia');
    }

    // El archivo sale del listado activo por defecto, pero se puede pedir explícitamente.
    public function test_cerrado_no_aparece_en_listado_por_defecto(): void
    {
        $autor = $this->crearUsuario('normal');
        $activa = $this->crearIncidencia($autor);
        $archivada = $this->crearIncidencia($autor);
        $archivada->update(['estado_incidencia' => 'CERRADO']);

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->getJson('/api/incidencias')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id_incidencia', $activa->id_incidencia);

        $this->getJson('/api/incidencias?estado=CERRADO')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id_incidencia', $archivada->id_incidencia);
    }

    // El job hourly archiva solo lo RESUELTO hace más de 24h y sin solicitud de reapertura pendiente.
    public function test_comando_archiva_resueltas_de_mas_de_24h_sin_reapertura(): void
    {
        $autor = $this->crearUsuario('normal');

        // El trigger tr_fecha_resolucion pisa fecha_resolucion a NOW() en la transición a RESUELTO;
        // por eso la fecha pasada se fija en un segundo update, ya sin cambio de estado de por medio.
        $vieja = $this->crearIncidencia($autor);
        $vieja->update(['estado_incidencia' => 'RESUELTO']);
        $vieja->update(['fecha_resolucion' => now()->subHours(30)]);

        $reciente = $this->crearIncidencia($autor);
        $reciente->update(['estado_incidencia' => 'RESUELTO']);
        $reciente->update(['fecha_resolucion' => now()->subHours(2)]);

        $conReapertura = $this->crearIncidencia($autor);
        $conReapertura->update(['estado_incidencia' => 'RESUELTO', 'reapertura_solicitada' => true]);
        $conReapertura->update(['fecha_resolucion' => now()->subHours(30)]);

        Artisan::call('incidencias:archivar-resueltas');

        $this->assertSame('CERRADO', $vieja->fresh()->estado_incidencia);
        $this->assertSame('RESUELTO', $reciente->fresh()->estado_incidencia);
        $this->assertSame('RESUELTO', $conReapertura->fresh()->estado_incidencia);

        // El historial atribuye el archivado al sistema (id_usuario NULL), no al reportador.
        $this->assertDatabaseHas('historial_estados', [
            'id_incidencia' => $vieja->id_incidencia,
            'id_usuario' => null,
            'estado_anterior' => 'RESUELTO',
            'estado_nuevo' => 'CERRADO',
        ]);
    }
}
