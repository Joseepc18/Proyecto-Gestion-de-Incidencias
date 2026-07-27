<?php

namespace Tests\Feature;

use App\Notifications\IncidenciaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// La campana (canal database) se escribe DENTRO de la petición, no en la cola: si fuera en cola y el worker
// estuviera caído, el aviso no aparecería ni dejaría rastro. Broadcast y correo sí siguen diferidos.
class CampanaEnLineaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // La suite corre con QUEUE_CONNECTION=sync, que ejecutaría todo en línea y haría pasar el test aunque
    // el arreglo no existiera. Por eso se fuerza una conexión que encola de verdad y nadie procesa: eso es
    // exactamente un worker caído.
    private function simularWorkerCaido(): void
    {
        config(['queue.default' => 'database']);
    }

    public function test_la_campana_se_escribe_aunque_el_worker_este_caido(): void
    {
        $usuario = $this->crearUsuario('normal');
        $this->simularWorkerCaido();

        $usuario->notify(new IncidenciaNotification('CAMBIO_ESTADO', 'Tu incidencia cambió de estado', null));

        $this->assertSame(1, $usuario->notifications()->count());
        $this->assertSame('Tu incidencia cambió de estado', $usuario->notifications()->first()->data['mensaje']);
    }

    public function test_el_broadcast_sigue_yendo_a_la_cola(): void
    {
        $usuario = $this->crearUsuario('normal');
        $this->simularWorkerCaido();

        $usuario->notify(new IncidenciaNotification('CAMBIO_ESTADO', 'Tu incidencia cambió de estado', null));

        // Queda esperando en la cola: es la mitad que sí puede diferirse, y probarlo evita que un cambio
        // futuro vuelva síncrona toda la notificación y meta el handshake de Reverb en la petición.
        $this->assertGreaterThan(0, DB::table('jobs')->count());
    }

    public function test_el_canal_database_declara_la_conexion_sincrona(): void
    {
        $conexiones = (new IncidenciaNotification('CAMBIO_ESTADO', 'x', null))->viaConnections();

        $this->assertSame('sync', $conexiones['database'] ?? null);
        $this->assertArrayNotHasKey('broadcast', $conexiones);
        $this->assertArrayNotHasKey('mail', $conexiones);
    }
}
