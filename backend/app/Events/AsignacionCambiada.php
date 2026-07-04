<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Se emite al asignar o quitar un técnico; refresca el detalle abierto y la cola del técnico afectado.
class AsignacionCambiada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // $accion ∈ {'asignada','quitada'}; $idUsuario es el técnico afectado.
    public function __construct(
        public int $idIncidencia,
        public string $accion,
        public string $rol,
        public int $idUsuario,
    ) {}

    // Canal de updates de la incidencia (detalle abierto) + canal privado del técnico (su cola en inicio).
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('incidencia.updates.'.$this->idIncidencia),
            new PrivateChannel('App.Models.User.'.$this->idUsuario),
        ];
    }

    // Nombre corto del evento; el front escucha ".AsignacionCambiada".
    public function broadcastAs(): string
    {
        return 'AsignacionCambiada';
    }

    public function broadcastWith(): array
    {
        return [
            'id_incidencia' => $this->idIncidencia,
            'accion' => $this->accion,
            'rol' => $this->rol,
            'id_usuario' => $this->idUsuario,
        ];
    }
}
