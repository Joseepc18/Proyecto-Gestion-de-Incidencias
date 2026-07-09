<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al cambiar estado o prioridad de una incidencia; refresca el detalle abierto y el tablero.
class IncidenciaActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    // Canal de updates de la incidencia (quien tiene el detalle abierto) + tablero de admins.
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('incidencia.updates.'.$this->incidencia->id_incidencia),
            new PresenceChannel('tablero'),
        ];
    }

    // Nombre corto del evento; el front escucha ".IncidenciaActualizada".
    public function broadcastAs(): string
    {
        return 'IncidenciaActualizada';
    }

    // Solo lo que el front repinta: badges de estado/prioridad y el flag de reapertura.
    public function broadcastWith(): array
    {
        return [
            'id_incidencia' => $this->incidencia->id_incidencia,
            'estado_incidencia' => $this->incidencia->estado_incidencia,
            'prioridad_incidencia' => $this->incidencia->prioridad_incidencia,
            'reapertura_pendiente' => (bool) $this->incidencia->reapertura_solicitada,
        ];
    }
}
