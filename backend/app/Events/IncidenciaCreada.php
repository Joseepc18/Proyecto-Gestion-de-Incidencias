<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al crear una incidencia; viaja al tablero de presencia para que los admins la vean aparecer sola.
class IncidenciaCreada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    // Tablero de presencia de administradores; la autorización vive en routes/channels.php.
    public function broadcastOn(): array
    {
        return [new PresenceChannel('tablero')];
    }

    // Nombre corto del evento; el front escucha ".IncidenciaCreada".
    public function broadcastAs(): string
    {
        return 'IncidenciaCreada';
    }

    // Payload mínimo: el front recarga el listado respetando sus filtros; el id/nombre sirven para el aviso.
    public function broadcastWith(): array
    {
        return [
            'id_incidencia' => $this->incidencia->id_incidencia,
            'nombre_incidencia' => $this->incidencia->nombre_incidencia,
        ];
    }
}
