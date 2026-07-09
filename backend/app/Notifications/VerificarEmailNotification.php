<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

// Correo Markdown para verificar el email. El secreto es la FIRMA de la URL (signed), validada en el backend.
// ShouldQueue: el envío se difiere a la cola para no bloquear la respuesta HTTP.
class VerificarEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifica tu correo')
            ->markdown('mail.verificar-email', [
                'nombre' => $notifiable->name,
                'url' => $this->urlVerificacion($notifiable),
            ]);
    }

    // URL firmada y temporal a la ruta del backend (la firma se valida server-side; el backend redirige al frontend).
    // Firma RELATIVA (absolute: false): cubre solo path+query, así el túnel Cloudflare (host/scheme del proxy) no invalida la firma.
    private function urlVerificacion(object $notifiable): string
    {
        $ruta = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            absolute: false
        );

        // El correo necesita un enlace absoluto; anteponemos la URL pública del backend a la ruta firmada relativa.
        return rtrim(config('app.url'), '/').$ruta;
    }
}
