<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al subir o eliminar una foto; refresca la galería de quien tenga el detalle abierto.
class EvidenciasActualizadas implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incidencia $incidencia) {}

    // Canal de updates de la incidencia (quien tiene el detalle abierto).
    public function broadcastOn(): array
    {
        return [new PrivateChannel('incidencia.updates.'.$this->incidencia->id_incidencia)];
    }

    // Nombre corto del evento; el front escucha ".EvidenciasActualizadas".
    public function broadcastAs(): string
    {
        return 'EvidenciasActualizadas';
    }

    // Lista completa: más simple que enviar el delta, y la galería es chica (máx. 6 fotos).
    public function broadcastWith(): array
    {
        return [
            'id_incidencia' => $this->incidencia->id_incidencia,
            'evidencias' => $this->incidencia->evidencias()->get()->toArray(),
        ];
    }
}
