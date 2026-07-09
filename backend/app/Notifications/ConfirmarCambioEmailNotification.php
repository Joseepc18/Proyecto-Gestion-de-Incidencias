<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

// Correo Markdown al correo NUEVO para confirmar el cambio. El secreto es la FIRMA de la URL (signed), validada en el backend.
// Se envía como notificación on-demand (a un correo aún no verificado); por eso lleva el usuario en el constructor.
class ConfirmarCambioEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu nuevo correo')
            ->markdown('mail.confirmar-cambio-email', [
                'nombre' => $this->user->name,
                'url' => $this->urlConfirmacion(),
            ]);
    }

    // URL firmada y temporal a la ruta del backend; el hash es sha1 del correo pendiente (se revalida al confirmar).
    private function urlConfirmacion(): string
    {
        $ruta = URL::temporarySignedRoute(
            'email.confirmar-cambio',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->user->getKey(),
                'hash' => sha1($this->user->email_pendiente),
            ],
            absolute: false
        );

        return rtrim(config('app.url'), '/').$ruta;
    }
}
