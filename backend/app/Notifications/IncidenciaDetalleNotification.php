<?php

namespace App\Notifications;

use App\Enums\RolAsignacion;
use App\Models\Incidencia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Correo único y detallado al ciudadano cuando su incidencia ya reúne admin + EN_PROCESO + prioridad + responsable.
// Solo mail (los micro-cambios ya viajan por la campanita); ShouldQueue: lo envía Horizon sin bloquear la respuesta.
// Guarda solo el id (escalar): recarga la incidencia al ejecutarse en la cola, sin serializar el modelo entero.
class IncidenciaDetalleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $idIncidencia) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $incidencia = Incidencia::with('asignaciones.usuario')->find($this->idIncidencia);

        // Si la incidencia desapareció entre el disparo y la cola, un correo mínimo evita reventar el job.
        if ($incidencia === null) {
            return (new MailMessage)
                ->subject('Actualización de tu incidencia')
                ->line('Tu incidencia ya está siendo atendida por nuestro equipo.');
        }

        $responsables = $this->nombresPorRol($incidencia, RolAsignacion::Responsable);
        $apoyos = $this->nombresPorRol($incidencia, RolAsignacion::Apoyo);

        return (new MailMessage)
            ->subject('Tu incidencia ya está en atención')
            ->markdown('mail.incidencia-detalle', [
                'nombre' => $notifiable->name,
                'incidencia' => $incidencia,
                'estado' => $incidencia->estado_incidencia->etiqueta(),
                'prioridad' => $incidencia->prioridad_incidencia->etiqueta(),
                'responsables' => $responsables,
                'apoyos' => $apoyos,
                'url' => $this->urlIncidencia(),
            ]);
    }

    // Nombres de los técnicos asignados con el rol dado, en un array limpio (sin nulos).
    private function nombresPorRol(Incidencia $incidencia, RolAsignacion $rol): array
    {
        return $incidencia->asignaciones
            ->where('rol_asignado', $rol->value)
            ->map(fn ($asignacion) => $asignacion->usuario?->name)
            ->filter()
            ->values()
            ->all();
    }

    // Enlace absoluto al detalle en el frontend.
    private function urlIncidencia(): string
    {
        return rtrim(config('services.frontend_url'), '/').'/detalle-incidencia/detalle-incidencia.html?id='.$this->idIncidencia;
    }
}
