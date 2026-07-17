<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al purgar una incidencia de la papelera; viaja al tablero para que la papelera se refresque sola.
class IncidenciaPurgada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $idIncidencia) {}

    // Tablero de admins (mismo canal que ya usa la eliminación).
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('tablero'),
        ];
    }

    // Nombre corto del evento; el front escucha ".IncidenciaPurgada".
    public function broadcastAs(): string
    {
        return 'IncidenciaPurgada';
    }

    // El front solo necesita el id.
    public function broadcastWith(): array
    {
        return ['id_incidencia' => $this->idIncidencia];
    }
}
