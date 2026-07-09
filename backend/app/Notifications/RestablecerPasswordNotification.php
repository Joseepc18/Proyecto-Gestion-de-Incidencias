<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Correo Markdown para restablecer contraseña. El secreto es el $token del broker (hasheado en BD, un solo uso).
// ShouldQueue: el envío se difiere a la cola para no bloquear la respuesta HTTP.
class RestablecerPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->markdown('mail.restablecer-password', [
                'nombre' => $notifiable->name,
                'url' => $this->urlReset($notifiable),
                'minutos' => config('auth.passwords.users.expire'),
            ]);
    }

    // El enlace apunta a una página del frontend que solo transporta el token de vuelta a la API.
    private function urlReset(object $notifiable): string
    {
        $frontend = rtrim(config('services.frontend_url'), '/');

        return $frontend.'/login/restablecer.html?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }
}
