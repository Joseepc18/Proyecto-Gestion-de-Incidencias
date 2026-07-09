<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Aviso de seguridad al correo VIEJO cuando se solicita cambiar el correo: alerta si no fue el dueño.
class AvisoCambioEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $emailNuevo) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Se solicitó cambiar el correo de tu cuenta')
            ->markdown('mail.aviso-cambio-email', [
                'nombre' => $notifiable->name,
                'emailNuevo' => $this->emailNuevo,
            ]);
    }
}
