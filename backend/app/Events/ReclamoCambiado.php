<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al reclamar o liberar el candado de atención; actualiza "Atendido por" en el detalle y el tablero.
class ReclamoCambiado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    // Canal de updates de la incidencia (detalle abierto) + tablero de admins.
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('incidencia.updates.'.$this->incidencia->id_incidencia),
            new PresenceChannel('tablero'),
        ];
    }

    // Nombre corto del evento; el front escucha ".ReclamoCambiado".
    public function broadcastAs(): string
    {
        return 'ReclamoCambiado';
    }

    // Quién atiende ahora (o null si se liberó) + el latido, para que el front reevalúe "vencido".
    public function broadcastWith(): array
    {
        $admin = $this->incidencia->adminAtiende;

        return [
            'id_incidencia' => $this->incidencia->id_incidencia,
            'id_admin_atiende' => $this->incidencia->id_admin_atiende,
            'admin_atiende' => $admin ? ['id' => $admin->id, 'name' => $admin->name] : null,
            'reclamo_visto_en' => $this->incidencia->reclamo_visto_en,
        ];
    }
}
