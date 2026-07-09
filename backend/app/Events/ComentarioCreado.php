<?php

namespace App\Events;

use App\Models\Comentario;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al crear un comentario; viaja por WebSocket a quienes están viendo el chat de esa incidencia.
class ComentarioCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Comentario $comentario) {}

    // Canal privado por incidencia; la autorización vive en routes/channels.php (reusa la policy verChat).
    public function broadcastOn(): array
    {
        return [new PrivateChannel('incidencia.'.$this->comentario->id_incidencia)];
    }

    // Nombre corto del evento; el front escucha ".ComentarioCreado".
    public function broadcastAs(): string
    {
        return 'ComentarioCreado';
    }

    // Carga mínima para pintar la burbuja en el front; nunca el email del autor.
    public function broadcastWith(): array
    {
        $c = $this->comentario->load('usuario.rol');

        return [
            'id_comentario' => $c->id_comentario,
            'id_incidencia' => $c->id_incidencia,
            'comentario' => $c->comentario,
            'created_at' => $c->created_at,
            'usuario' => [
                'id' => $c->usuario->id,
                'name' => $c->usuario->name,
                'foto_perfil' => $c->usuario->foto_perfil_url,
                'rol' => ['nombre_rol' => $c->usuario->rol?->nombre_rol],
            ],
        ];
    }
}
