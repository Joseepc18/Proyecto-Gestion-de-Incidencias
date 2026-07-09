<?php

namespace App\Events;

use App\Models\Comentario;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al editar un comentario; viaja por WebSocket a quienes están viendo el chat de esa incidencia.
class ComentarioActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Comentario $comentario) {}

    // Mismo canal privado que ComentarioCreado (autorización en routes/channels.php).
    public function broadcastOn(): array
    {
        return [new PrivateChannel('incidencia.'.$this->comentario->id_incidencia)];
    }

    public function broadcastAs(): string
    {
        return 'ComentarioActualizado';
    }

    // Carga mínima para que el front reemplace el texto de la burbuja ya pintada.
    public function broadcastWith(): array
    {
        return [
            'id_comentario' => $this->comentario->id_comentario,
            'id_incidencia' => $this->comentario->id_incidencia,
            'comentario' => $this->comentario->comentario,
        ];
    }
}
