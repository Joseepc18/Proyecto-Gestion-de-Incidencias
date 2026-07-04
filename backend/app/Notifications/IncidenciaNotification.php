<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

// Notificación única y reutilizable del dominio: el "tipo" viaja como dato, igual que lo consume el front.
// Multicanal: se guarda en la tabla notifications (database) y se emite por Reverb (broadcast).
// El canal mail queda pendiente para la sesión de Mail; no se declara en via() todavía.
class IncidenciaNotification extends Notification
{
    // $contador solo lo usa COMENTARIO (agrupación); el resto queda en 1.
    public function __construct(
        public string $tipo,
        public string $mensaje,
        public ?int $idIncidencia,
        public int $contador = 1,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // Lo que se guarda en la columna data (json) de la tabla notifications.
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'id_incidencia' => $this->idIncidencia,
            'contador' => $this->contador,
        ];
    }

    // Payload que viaja por WebSocket al canal privado del usuario (App.Models.User.{id}).
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
