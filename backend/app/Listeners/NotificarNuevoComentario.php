<?php

namespace App\Listeners;

use App\Enums\RolAsignacion;
use App\Events\ComentarioCreado;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaNotification;

// Reemplaza al viejo trigger fn_notificar_nuevo_comentario: avisa al reportador, a quienes gestionan
// y al técnico responsable (nunca al autor), consolidando en la notificación de COMENTARIO sin leer.
class NotificarNuevoComentario
{
    public function handle(ComentarioCreado $evento): void
    {
        $comentario = $evento->comentario;
        $incidencia = Incidencia::find($comentario->id_incidencia);
        if (! $incidencia) {
            return;
        }

        $nombre = $incidencia->nombre_incidencia;

        // Destinatarios: reportador + gestores + responsable, sin duplicados y nunca el autor.
        $ids = collect([$incidencia->id_usuario])
            ->merge(User::conPermiso('incidencias.gestionar')->pluck('id'))
            ->merge($incidencia->asignaciones()->where('rol_asignado', RolAsignacion::Responsable->value)->pluck('id_usuario'))
            ->unique()
            ->reject(fn ($id) => (int) $id === $comentario->id_usuario);

        foreach (User::whereIn('id', $ids)->get() as $usuario) {
            $this->notificar($usuario, $incidencia->id_incidencia, $nombre);
        }
    }

    // Si ya hay una notificación de COMENTARIO sin leer de esa incidencia, incrementa el contador; si no, crea una.
    private function notificar(User $usuario, int $idIncidencia, string $nombre): void
    {
        $existente = $usuario->unreadNotifications()
            ->where('data->tipo', 'COMENTARIO')
            ->where('data->id_incidencia', (string) $idIncidencia)
            ->first();

        if (! $existente) {
            $usuario->notify(new IncidenciaNotification('COMENTARIO', 'Nuevo comentario en la incidencia: '.$nombre, $idIncidencia));

            return;
        }

        $data = $existente->data;
        $data['contador'] = ($data['contador'] ?? 1) + 1;
        $data['mensaje'] = $data['contador'].' comentarios nuevos en la incidencia: '.$nombre;
        $existente->data = $data;
        // Sube al tope de la campana (ordena por created_at), como hacía el UPSERT del trigger.
        $existente->created_at = now();
        $existente->save();
    }
}
