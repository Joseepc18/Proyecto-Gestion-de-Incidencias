<?php

namespace App\Listeners;

use App\Enums\EstadoIncidencia;
use App\Events\IncidenciaCambioEstado;
use App\Models\User;
use App\Notifications\IncidenciaNotification;

// Reemplaza al viejo trigger fn_notificar_cambio_estado + los INSERT del SP resolver_incidencia:
// avisa al reportador y a los técnicos asignados (nunca al actor) según la transición.
class EnviarNotificacionCambioEstado
{
    public function handle(IncidenciaCambioEstado $evento): void
    {
        $incidencia = $evento->incidencia;
        $nombre = $incidencia->nombre_incidencia;
        $actor = $evento->actorId;

        [$tipo, $msgReportador, $msgTecnicos] = $this->mensajes($evento->estadoAnterior, $evento->estadoNuevo, $nombre);

        // Al reportador, salvo que sea quien hizo el cambio. correo: true → también le llega por email.
        if ($incidencia->id_usuario !== $actor && ($reportador = User::find($incidencia->id_usuario))) {
            $reportador->notify(new IncidenciaNotification($tipo, $msgReportador, $incidencia->id_incidencia, correo: true));
        }

        // A cada técnico asignado (responsable y apoyo), menos el actor.
        // Con actor null (sistema) no se excluye a nadie: 'id != NULL' en SQL dejaría fuera a todos.
        $tecnicos = User::whereIn('id', $incidencia->asignaciones()->pluck('id_usuario'))
            ->when($actor !== null, fn ($q) => $q->where('id', '!=', $actor))
            ->get();

        foreach ($tecnicos as $tecnico) {
            $tecnico->notify(new IncidenciaNotification($tipo, $msgTecnicos, $incidencia->id_incidencia));
        }
    }

    // Devuelve [tipo, mensaje al reportador, mensaje a los técnicos] según la transición.
    private function mensajes(string $anterior, string $nuevo, string $nombre): array
    {
        // RESUELTO: antes lo mandaba el SP resolver_incidencia.
        if ($nuevo === EstadoIncidencia::Resuelto->value) {
            return ['CAMBIO_ESTADO', 'Tu incidencia ha sido resuelta: '.$nombre, 'La incidencia ha sido marcada como resuelta: '.$nombre];
        }

        // Única reapertura real: RESUELTO -> EN_PROCESO (RESUELTO -> CERRADO es archivado, cae al genérico).
        if ($anterior === EstadoIncidencia::Resuelto->value && $nuevo === EstadoIncidencia::EnProceso->value) {
            return ['SOLICITUD_REAPERTURA', 'Tu incidencia fue reabierta: '.$nombre, 'La incidencia fue reabierta: '.$nombre];
        }

        return ['CAMBIO_ESTADO', 'Tu incidencia cambió a '.$nuevo.': '.$nombre, 'La incidencia cambió a '.$nuevo.': '.$nombre];
    }
}
