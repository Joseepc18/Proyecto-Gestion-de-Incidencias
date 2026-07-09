<?php

namespace Tests\Feature;

use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaDetalleNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// El correo de detalle al ciudadano es un hito idempotente: sale UNA sola vez y solo cuando la incidencia
// reúne las 4 condiciones (admin + EN_PROCESO + prioridad asignada + responsable), sin importar el orden.
class CorreoDetalleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $reportador;

    private User $admin;

    private User $tecnico;

    private Incidencia $incidencia;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->reportador = $this->crearUsuario('normal');
        $this->admin = $this->crearUsuario('admin');
        $this->tecnico = $this->crearUsuario('tecnico');

        // Nace sin prioridad (SIN_ASIGNAR), PENDIENTE, sin admin ni responsable.
        $this->incidencia = $this->crearIncidencia($this->reportador, ['prioridad_incidencia' => 'SIN_ASIGNAR']);

        Sanctum::actingAs($this->admin);
    }

    private function reclamar(): void
    {
        $this->postJson("/api/incidencias/{$this->incidencia->id_incidencia}/reclamar")->assertOk();
    }

    private function ponerPrioridad(string $prioridad = 'ALTA'): void
    {
        $this->putJson("/api/incidencias/{$this->incidencia->id_incidencia}", ['prioridad_incidencia' => $prioridad])->assertOk();
    }

    private function asignarResponsable(): void
    {
        $this->postJson("/api/incidencias/{$this->incidencia->id_incidencia}/asignaciones", [
            'id_usuario' => $this->tecnico->id,
            'rol_asignado' => 'RESPONSABLE',
        ])->assertCreated();
    }

    private function pasarAEnProceso(): void
    {
        $this->patchJson("/api/incidencias/{$this->incidencia->id_incidencia}/estado", ['estado_incidencia' => 'EN_PROCESO'])->assertOk();
    }

    private function assertCorreoDetalle(int $veces): void
    {
        Notification::assertSentToTimes($this->reportador, IncidenciaDetalleNotification::class, $veces);
    }

    public function test_correo_se_envia_una_sola_vez_al_completar_las_cuatro_condiciones(): void
    {
        $this->reclamar();
        $this->ponerPrioridad();
        $this->asignarResponsable();

        // Aún falta EN_PROCESO: nada de correo todavía.
        $this->assertCorreoDetalle(0);

        // La última pieza (EN_PROCESO) dispara el único correo.
        $this->pasarAEnProceso();
        $this->assertCorreoDetalle(1);

        // La bandera lo blinda: acciones de gestión posteriores no reenvían.
        $this->ponerPrioridad('MEDIA');
        $this->assertCorreoDetalle(1);

        $this->assertTrue($this->incidencia->fresh()->correo_detalle_enviado);
    }

    public function test_no_se_envia_si_falta_el_responsable(): void
    {
        $this->reclamar();
        $this->ponerPrioridad();
        $this->pasarAEnProceso();

        // Admin + prioridad + EN_PROCESO, pero sin responsable: el hito no se cumple.
        $this->assertCorreoDetalle(0);
        $this->assertFalse($this->incidencia->fresh()->correo_detalle_enviado);

        // Al asignar el responsable se completa y sale el correo.
        $this->asignarResponsable();
        $this->assertCorreoDetalle(1);
    }

    public function test_no_se_envia_mientras_la_prioridad_siga_sin_asignar(): void
    {
        $this->reclamar();
        $this->asignarResponsable();
        $this->pasarAEnProceso();

        // Todo listo salvo la prioridad (sigue SIN_ASIGNAR): sin correo.
        $this->assertCorreoDetalle(0);

        $this->ponerPrioridad();
        $this->assertCorreoDetalle(1);
    }

    public function test_se_dispara_igual_con_otro_orden_de_las_acciones(): void
    {
        // Orden distinto: primero EN_PROCESO y responsable; la última pieza (prioridad) es la que dispara.
        $this->reclamar();
        $this->pasarAEnProceso();
        $this->asignarResponsable();
        $this->assertCorreoDetalle(0);

        $this->ponerPrioridad();
        $this->assertCorreoDetalle(1);
    }
}
