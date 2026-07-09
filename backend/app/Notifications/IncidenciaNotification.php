<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Notificación única y reutilizable: multicanal (BD, broadcast Reverb y, si $correo es true, email Markdown).
// ShouldQueue: el envío completo se difiere a la cola para no bloquear la respuesta HTTP.
class IncidenciaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $contador solo lo usa COMENTARIO (agrupación); el resto queda en 1.
    public function __construct(
        public string $tipo,
        public string $mensaje,
        public ?int $idIncidencia,
        public int $contador = 1,
        public bool $correo = false,
    ) {}

    public function via(object $notifiable): array
    {
        $canales = ['database', 'broadcast'];

        if ($this->correo) {
            $canales[] = 'mail';
        }

        return $canales;
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

    // Correo Markdown: asignación usa su propia plantilla; el resto (cambio de estado) la genérica.
    public function toMail(object $notifiable): MailMessage
    {
        $esAsignacion = $this->tipo === 'ASIGNACION';

        return (new MailMessage)
            ->subject($esAsignacion ? 'Nueva asignación de incidencia' : 'Actualización de tu incidencia')
            ->markdown($esAsignacion ? 'mail.asignacion' : 'mail.cambio-estado', [
                'nombre' => $notifiable->name,
                'mensaje' => $this->mensaje,
                'url' => $this->urlIncidencia(),
            ]);
    }

    // Enlace absoluto al detalle en el frontend; null si la incidencia ya no existe (no se pinta el botón).
    private function urlIncidencia(): ?string
    {
        if ($this->idIncidencia === null) {
            return null;
        }

        return rtrim(config('services.frontend_url'), '/').'/detalle-incidencia/detalle-incidencia.html?id='.$this->idIncidencia;
    }
}
