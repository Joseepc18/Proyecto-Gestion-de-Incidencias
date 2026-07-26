<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use App\Models\Ciudad;
use App\Models\Incidencia;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos())->assertCreated();

        // Queda guardada y asociada a quien la reportó.
        $this->assertDatabaseHas('incidencias', [
            'nombre_incidencia' => 'Bache peligroso en la avenida principal',
            'id_usuario' => $usuario->id,
        ]);
    }

    public function test_crear_incidencia_sin_datos_requeridos_devuelve_422(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        // La prioridad ya no es obligatoria (entra como SIN_ASIGNAR por defecto).
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
        $this->assertDatabaseMissing('incidencias', ['nombre_incidencia' => 'Bache peligroso en la avenida principal']);
        $this->assertDatabaseHas('bitacora_errores', ['tipo_error' => 'ARCHIVO']);
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

    // El ciudadano ve lo archivado como "Resuelto" (estados.js); su filtro ?estado=RESUELTO debe
    // traer también lo CERRADO, o la mitad de sus resueltas "desaparecerían" al filtrar.
    public function test_ciudadano_filtra_resuelto_y_tambien_trae_lo_archivado(): void
    {
        $autor = $this->crearUsuario('normal');

        $this->crearIncidencia($autor, [
            'nombre_incidencia' => 'Fuga resuelta y aun visible',
            'estado_incidencia' => 'RESUELTO',
        ]);
        $this->crearIncidencia($autor, [
            'nombre_incidencia' => 'Bache resuelto y ya archivado',
            'estado_incidencia' => 'CERRADO',
        ]);
        $this->crearIncidencia($autor, [
            'nombre_incidencia' => 'Semaforo aun pendiente',
            'estado_incidencia' => 'PENDIENTE',
        ]);

        Sanctum::actingAs($autor);

        $this->getJson('/api/incidencias?estado=RESUELTO')
            ->assertOk()->assertJsonPath('total', 2);

        // El admin, en cambio, sigue viendo el filtro exacto (RESUELTO y CERRADO no se mezclan).
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/incidencias?estado=RESUELTO')
            ->assertOk()->assertJsonPath('total', 1);
    }

    // El "Todos" del ciudadano debe incluir lo archivado (que él ve como "Resuelto"); si no,
    // filtrar por "Resuelto" mostraría más incidencias que "Todos", que es lo contrario de lo esperado.
    public function test_ciudadano_todos_incluye_lo_archivado(): void
    {
        $autor = $this->crearUsuario('normal');

        $this->crearIncidencia($autor, ['estado_incidencia' => 'RESUELTO']);
        $this->crearIncidencia($autor, ['estado_incidencia' => 'CERRADO']);
        $this->crearIncidencia($autor, ['estado_incidencia' => 'PENDIENTE']);

        Sanctum::actingAs($autor);

        // "Todos" (sin estado) trae las 3, incluida la archivada.
        $this->getJson('/api/incidencias')
            ->assertOk()->assertJsonPath('total', 3);

        // Al admin/técnico, en cambio, "Todos" sigue ocultando lo CERRADO (listado activo).
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/incidencias')
            ->assertOk()->assertJsonPath('total', 2);
    }

    public function test_cambiar_estado_respeta_las_dos_guardas(): void
    {
        // Guarda de ROL: el técnico responsable solo cierra EN_PROCESO→RESUELTO; PENDIENTE→RESUELTO
        // sí existe en el grafo, así que lo corta la regla de rol (no la estructural).
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $responsable = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $responsable->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);
        Sanctum::actingAs($responsable);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'RESUELTO'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Tu rol no puede realizar ese cambio de estado']);

        // Guarda ESTRUCTURAL: EN_PROCESO→PENDIENTE no existe en el grafo; ni el admin puede hacerlo.
        $enProceso = $this->crearIncidencia($this->crearUsuario('normal'), ['estado_incidencia' => 'EN_PROCESO']);
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->postJson("/api/incidencias/{$enProceso->id_incidencia}/reclamar")->assertOk();

        $this->patchJson("/api/incidencias/{$enProceso->id_incidencia}/estado", ['estado_incidencia' => 'PENDIENTE'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Transición de estado no permitida']);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $enProceso->id_incidencia,
            'estado_incidencia' => 'EN_PROCESO',
        ]);
    }

    // A1: el job de escalado sube un nivel a las incidencias viejas sin atender; SIN_ASIGNAR
    // (prioridad por defecto del reporte ciudadano) salta a MEDIA en vez de reventar con update a null.
    public function test_escalar_antiguas_sube_prioridad_y_no_revienta_con_sin_asignar(): void
    {
        $reportador = $this->crearUsuario('normal');
        // Un admin (tiene incidencias.gestionar) para que la notificación de escalado tenga destinatario.
        $admin = $this->crearUsuario('admin');

        // Viejas (>24h), PENDIENTE y sin reclamar: una por cada prioridad de partida.
        $sinAsignar = $this->incidenciaAntigua($reportador, 'SIN_ASIGNAR');
        $baja = $this->incidenciaAntigua($reportador, 'BAJA');
        $media = $this->incidenciaAntigua($reportador, 'MEDIA');
        $alta = $this->incidenciaAntigua($reportador, 'ALTA');
        // Reciente (<24h): no debe tocarse.
        $reciente = $this->crearIncidencia($reportador, ['prioridad_incidencia' => 'BAJA']);

        Artisan::call('incidencias:escalar-antiguas');

        // SIN_ASIGNAR salta directo a MEDIA (antes: update a null → viola el CHECK/NOT NULL).
        $this->assertSame('MEDIA', $sinAsignar->fresh()->prioridad_incidencia->value);
        $this->assertSame('MEDIA', $baja->fresh()->prioridad_incidencia->value);
        $this->assertSame('ALTA', $media->fresh()->prioridad_incidencia->value);
        // ALTA ya es el tope y queda fuera de la consulta.
        $this->assertSame('ALTA', $alta->fresh()->prioridad_incidencia->value);
        // La reciente no cambia.
        $this->assertSame('BAJA', $reciente->fresh()->prioridad_incidencia->value);

        // El admin recibe el aviso de escalado.
        $this->assertNotificado($admin, 'ESCALADO');
    }

    // M2: resolver dos veces la misma incidencia no duplica la notificación ni el historial.
    // La segunda pasada la corta el guard del controller; el FOR UPDATE del SP cubre el caso concurrente (no reproducible en un test secuencial).
    public function test_doble_resolucion_no_duplica_notificacion_ni_historial(): void
    {
        $reportador = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($reportador, ['estado_incidencia' => 'EN_PROCESO']);
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        // Primer resolver: pasa y notifica al reportador.
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'RESUELTO'])
            ->assertOk();

        // Segundo resolver sobre la ya resuelta: el guard lo corta sin volver a disparar el evento.
        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", ['estado_incidencia' => 'RESUELTO'])
            ->assertStatus(422)
            ->assertJson(['message' => 'No se puede cambiar el estado de una incidencia ya resuelta']);

        // Una sola notificación de cambio de estado y una sola transición a RESUELTO en el historial.
        $notificaciones = $this->notificacionesDe($reportador, 'CAMBIO_ESTADO');
        $this->assertCount(1, $notificaciones);

        // El aviso al reportador incluye el plazo para pedir reapertura, tomado de la constante (no hardcodeado).
        $this->assertStringContainsString(
            'Tienes '.Incidencia::HORAS_PARA_ARCHIVAR.' horas para solicitar la reapertura',
            $notificaciones->first()->data['mensaje']
        );

        $this->assertSame(1, DB::table('historial_estados')
            ->where('id_incidencia', $incidencia->id_incidencia)
            ->where('estado_nuevo', 'RESUELTO')
            ->count());
    }

    // Incidencia vieja (>24h), PENDIENTE y sin reclamar, con la prioridad de partida dada.
    private function incidenciaAntigua(User $reportador, string $prioridad): Incidencia
    {
        $incidencia = $this->crearIncidencia($reportador, ['prioridad_incidencia' => $prioridad]);
        Incidencia::where('id_incidencia', $incidencia->id_incidencia)
            ->update(['created_at' => now()->subHours(25)]);

        return $incidencia;
    }

    public function test_se_puede_asignar_un_tecnico(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $tecnico = $this->crearUsuario('tecnico');
        Sanctum::actingAs($this->crearUsuario('admin'));

        // El admin debe reclamar la incidencia antes de gestionarla (asignar).
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

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

    // Reclamo v2: si el lease del dueño vence (dejó de latir), otro admin puede tomarlo.
    public function test_admin_puede_tomar_un_reclamo_vencido(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin1 = $this->crearUsuario('admin');
        $admin2 = $this->crearUsuario('admin');

        Sanctum::actingAs($admin1);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        // Pasa el TTL sin latido: el reclamo de admin1 queda vencido.
        $this->travel(Incidencia::RECLAMO_TTL_SEGUNDOS + 10)->seconds();

        Sanctum::actingAs($admin2);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertOk()
            ->assertJsonPath('id_admin_atiende', $admin2->id);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => $admin2->id,
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
        $this->assertSame('CERRADO', $incidencia->estado_incidencia->value);
        $this->assertNotNull($incidencia->fecha_resolucion);
    }

    // Archivar suelta el candado en el mismo update: una cerrada no se gestiona, así que no puede quedar a cargo de nadie.
    public function test_archivar_libera_el_candado_de_atencion(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin = $this->crearUsuario('admin');

        Sanctum::actingAs($admin);
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'fecha_resolucion' => now()]);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/archivar")
            ->assertOk()
            ->assertJsonPath('id_admin_atiende', null)
            ->assertJsonPath('admin_atiende', null);

        $incidencia->refresh();
        $this->assertNull($incidencia->id_admin_atiende);
        $this->assertNull($incidencia->reclamo_visto_en);
    }

    // Liberar ya no se autoriza con el gate de reclamar (que niega en archivadas): si no, una fila mal cerrada queda trabada para siempre.
    public function test_el_candado_de_una_incidencia_archivada_se_puede_liberar(): void
    {
        $admin = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));

        // Reproduce una fila anterior al arreglo: archivada y con el reclamo todavía puesto.
        $incidencia->update([
            'estado_incidencia' => 'CERRADO',
            'id_admin_atiende' => $admin->id,
            'reclamo_visto_en' => now(),
        ]);

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertOk()
            ->assertJsonPath('id_admin_atiende', null);

        $this->assertNull($incidencia->fresh()->id_admin_atiende);
    }

    // El latido solo refresca los reclamos de incidencias activas: en las archivadas ya no hay candado que mantener vivo.
    public function test_el_latido_no_toca_las_incidencias_archivadas(): void
    {
        $admin = $this->crearUsuario('admin');
        $autor = $this->crearUsuario('normal');

        $activa = $this->crearIncidencia($autor);
        $activa->update(['id_admin_atiende' => $admin->id, 'reclamo_visto_en' => now()]);

        $archivada = $this->crearIncidencia($autor);
        $archivada->update([
            'estado_incidencia' => 'CERRADO',
            'id_admin_atiende' => $admin->id,
            'reclamo_visto_en' => now(),
        ]);

        $latidoPrevio = $archivada->fresh()->reclamo_visto_en;

        $this->travel(60)->seconds();
        Sanctum::actingAs($admin);
        $this->postJson('/api/incidencias/reclamo/heartbeat')->assertNoContent();

        // La activa recibe el latido nuevo; la archivada se queda exactamente como estaba.
        $this->assertTrue($activa->fresh()->reclamo_visto_en->greaterThan($latidoPrevio));
        $this->assertTrue($archivada->fresh()->reclamo_visto_en->equalTo($latidoPrevio));
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

        $this->assertSame('CERRADO', $vieja->fresh()->estado_incidencia->value);
        $this->assertSame('RESUELTO', $reciente->fresh()->estado_incidencia->value);
        $this->assertSame('RESUELTO', $conReapertura->fresh()->estado_incidencia->value);

        // El historial atribuye el archivado al sistema (id_usuario NULL), no al reportador.
        $this->assertDatabaseHas('historial_estados', [
            'id_incidencia' => $vieja->id_incidencia,
            'id_usuario' => null,
            'estado_anterior' => 'RESUELTO',
            'estado_nuevo' => 'CERRADO',
        ]);
    }

    // El auto-archivado es el camino normal de toda resuelta: si no soltara el candado, casi todas terminarían trabadas.
    public function test_comando_de_archivado_libera_el_candado_de_atencion(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $admin = $this->crearUsuario('admin');

        $incidencia->update(['estado_incidencia' => 'RESUELTO', 'id_admin_atiende' => $admin->id, 'reclamo_visto_en' => now()]);
        $incidencia->update(['fecha_resolucion' => now()->subHours(30)]);

        Artisan::call('incidencias:archivar-resueltas');

        $incidencia->refresh();
        $this->assertSame('CERRADO', $incidencia->estado_incidencia->value);
        $this->assertNull($incidencia->id_admin_atiende);
        $this->assertNull($incidencia->reclamo_visto_en);
    }
}
