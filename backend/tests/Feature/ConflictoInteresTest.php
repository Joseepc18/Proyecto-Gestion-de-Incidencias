<?php

namespace Tests\Feature;

use App\Models\AsignacionIncidencia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Reportar es un permiso configurable (incidencias.crear) y quien reporta no atiende lo suyo:
// ni gestionándolo como Supervisor ni siendo asignado como técnico.
class ConflictoInteresTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_el_ciudadano_nace_con_el_permiso_de_reportar(): void
    {
        Sanctum::actingAs($this->crearUsuario('normal'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos())->assertCreated();
    }

    public function test_sin_el_permiso_de_reportar_no_se_puede_crear(): void
    {
        // admin, tecnico y super_admin nacen sin incidencias.crear.
        foreach (['admin', 'tecnico', 'super_admin'] as $rol) {
            Sanctum::actingAs($this->crearUsuario($rol));

            $this->postJson('/api/incidencias', $this->datosIncidenciaValidos())
                ->assertStatus(403);
        }

        $this->assertDatabaseCount('incidencias', 0);
    }

    // H-07: el Administrador del Sistema es view-only, así que ya no puede hacer nacer una incidencia en EN_PROCESO.
    public function test_el_super_admin_con_permiso_de_reportar_no_fija_estado_ni_prioridad(): void
    {
        $this->darPermisoAlRol('super_admin', 'incidencias.crear');
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos([
            'estado_incidencia' => 'EN_PROCESO',
            'prioridad_incidencia' => 'ALTA',
        ]))->assertCreated();

        // Sin incidencias.gestionar esos dos campos ni siquiera se validan: entra con los valores por defecto.
        $this->assertDatabaseHas('incidencias', [
            'estado_incidencia' => 'PENDIENTE',
            'prioridad_incidencia' => 'SIN_ASIGNAR',
        ]);
    }

    public function test_el_supervisor_no_puede_reclamar_lo_que_el_mismo_reporto(): void
    {
        $supervisor = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($supervisor);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertStatus(403);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => null,
        ]);
    }

    // Aunque el candado ya estuviera puesto a su nombre (reclamo viejo), gestionar lo propio sigue negado.
    public function test_el_supervisor_no_gestiona_lo_que_el_mismo_reporto(): void
    {
        $supervisor = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($supervisor, ['id_admin_atiende' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertStatus(403);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'estado_incidencia' => 'PENDIENTE',
        ]);
    }

    // El camino legítimo del autor no se rompe: corregir el propio reporte en PENDIENTE sigue permitido.
    public function test_el_supervisor_autor_si_corrige_su_reporte_mientras_esta_pendiente(): void
    {
        $supervisor = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($supervisor);
        Sanctum::actingAs($supervisor);

        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'nombre_incidencia' => 'Poste caído junto a la escuela',
        ])->assertOk();

        // Pero eso es la vía del autor, no gestión: la prioridad no entra en las reglas y se descarta.
        $this->putJson("/api/incidencias/{$incidencia->id_incidencia}", [
            'prioridad_incidencia' => 'ALTA',
        ])->assertOk();

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'prioridad_incidencia' => 'MEDIA',
        ]);
    }

    // H-44: responder en el chat del propio reporte es la vía del autor, no gestión; antes el conflicto
    // de interés dejaba al Supervisor autor sin poder contestarle al Supervisor que sí la atiende.
    public function test_el_supervisor_autor_si_comenta_en_el_chat_de_su_reporte(): void
    {
        $supervisor = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($supervisor, ['id_admin_atiende' => $this->crearUsuario('admin')->id]);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios", [
            'comentario' => 'Adjunto la referencia exacta del poste.',
        ])->assertCreated();
    }

    // El autor sin rol gestor nunca pasó por la rama de gestión: que la corrección no le cambie nada.
    public function test_el_tecnico_autor_sigue_comentando_en_su_reporte(): void
    {
        $tecnico = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($tecnico);
        Sanctum::actingAs($tecnico);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios", [
            'comentario' => 'Ya pasé por el sitio.',
        ])->assertCreated();
    }

    // La excepción es solo para el autor: sobre una incidencia ajena el Administrador del Sistema sigue siendo view-only.
    public function test_el_super_admin_sigue_sin_escribir_en_el_chat_de_una_incidencia_ajena(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->getJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios")->assertOk();
        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/comentarios", [
            'comentario' => 'Hola',
        ])->assertStatus(403);
    }

    public function test_no_se_puede_asignar_como_tecnico_al_autor_de_la_incidencia(): void
    {
        $tecnico = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($tecnico);
        $supervisor = $this->crearUsuario('admin');
        $incidencia->update(['id_admin_atiende' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        foreach (['RESPONSABLE', 'APOYO'] as $rolAsignado) {
            $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/asignaciones", [
                'id_usuario' => $tecnico->id,
                'rol_asignado' => $rolAsignado,
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('id_usuario');
        }

        $this->assertDatabaseCount('asignaciones_incidencia', 0);
    }

    // Última capa: el procedimiento almacenado rechaza la asignación aunque se le llame saltándose el FormRequest.
    public function test_el_procedimiento_almacenado_rechaza_asignar_al_autor(): void
    {
        $tecnico = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($tecnico);

        $this->expectException(QueryException::class);

        DB::statement('CALL asignar_tecnico(?, ?, ?)', [
            $incidencia->id_incidencia,
            $tecnico->id,
            'RESPONSABLE',
        ]);
    }

    // Un técnico distinto del autor sí se asigna con normalidad (la regla no bloquea el flujo normal).
    public function test_otro_tecnico_si_se_puede_asignar(): void
    {
        $autor = $this->crearUsuario('tecnico');
        $otroTecnico = $this->crearUsuario('tecnico');
        $incidencia = $this->crearIncidencia($autor);
        $supervisor = $this->crearUsuario('admin');
        $incidencia->update(['id_admin_atiende' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/asignaciones", [
            'id_usuario' => $otroTecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ])->assertCreated();

        $this->assertDatabaseHas('asignaciones_incidencia', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $otroTecnico->id,
        ]);
    }

    // H-40: la llave maestra del Administrador del Sistema suelta un candado ajeno aunque siga activo.
    public function test_el_super_admin_libera_el_candado_de_otro_administrador(): void
    {
        $supervisor = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'), [
            'id_admin_atiende' => $supervisor->id,
            'reclamo_visto_en' => now(),
        ]);

        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertOk();

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => null,
        ]);
    }

    // La llave maestra es solo del super_admin: un Supervisor ajeno sigue sin poder forzar un lease vivo.
    public function test_otro_supervisor_no_fuerza_un_candado_activo(): void
    {
        $dueno = $this->crearUsuario('admin');
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'), [
            'id_admin_atiende' => $dueno->id,
            'reclamo_visto_en' => now(),
        ]);

        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->deleteJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")
            ->assertStatus(422);

        $this->assertDatabaseHas('incidencias', [
            'id_incidencia' => $incidencia->id_incidencia,
            'id_admin_atiende' => $dueno->id,
        ]);
    }

    // El super_admin sigue siendo view-only: la llave maestra no le abre el resto de la gestión.
    public function test_la_llave_maestra_no_le_da_al_super_admin_el_resto_de_la_gestion(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson("/api/incidencias/{$incidencia->id_incidencia}/reclamar")->assertStatus(403);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'EN_PROCESO',
        ])->assertStatus(403);
    }

    // El técnico responsable sigue trabajando normalmente sobre una incidencia ajena.
    public function test_el_tecnico_responsable_de_una_incidencia_ajena_no_se_ve_afectado(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'), ['estado_incidencia' => 'EN_PROCESO']);
        $tecnico = $this->crearUsuario('tecnico');
        AsignacionIncidencia::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ]);

        Sanctum::actingAs($tecnico);

        $this->patchJson("/api/incidencias/{$incidencia->id_incidencia}/estado", [
            'estado_incidencia' => 'RESUELTO',
        ])->assertOk();
    }
}
