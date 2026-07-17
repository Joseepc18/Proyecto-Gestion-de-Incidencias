<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al enviar una incidencia a la papelera; viaja al tablero para que desaparezca sola de la lista.
class IncidenciaEliminada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $idIncidencia) {}

    // Tablero de admins (quita la fila) + canal de la incidencia (saca al que tiene el detalle abierto).
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('tablero'),
            new PrivateChannel('incidencia.updates.'.$this->idIncidencia),
        ];
    }

    // Nombre corto del evento; el front escucha ".IncidenciaEliminada".
    public function broadcastAs(): string
    {
        return 'IncidenciaEliminada';
    }

    // El front solo necesita el id para quitar la fila.
    public function broadcastWith(): array
    {
        return ['id_incidencia' => $this->idIncidencia];
    }
}
