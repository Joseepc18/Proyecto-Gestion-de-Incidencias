<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Avisa al ciudadano el desenlace de su solicitud de reactivación; antes un rechazo era silencioso.
class ReactivacionResueltaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public bool $aprobada) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asunto = $this->aprobada
            ? 'Tu cuenta fue reactivada'
            : 'Tu solicitud de reactivación fue rechazada';

        return (new MailMessage)
            ->subject($asunto)
            ->markdown('mail.reactivacion-resuelta', [
                'nombre' => $notifiable->name,
                'aprobada' => $this->aprobada,
            ]);
    }
}
