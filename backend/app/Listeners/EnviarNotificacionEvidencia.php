<?php

namespace App\Listeners;

use App\Events\EvidenciaSubida;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Support\Facades\Notification;

// Notifica según quién sube la foto (nunca al actor): el ciudadano → admins+técnicos; el técnico/admin → reportador.
class EnviarNotificacionEvidencia
{
    public function handle(EvidenciaSubida $evento): void
    {
        $incidencia = $evento->incidencia;
        $user = $evento->actor;
        $nombre = $incidencia->nombre_incidencia;

        if ($user->id === $incidencia->id_usuario) {
            $destinos = User::conPermiso('incidencias.gestionar')
                ->pluck('id')
                ->merge($incidencia->asignaciones()->pluck('id_usuario'))
                ->unique()
                ->reject(fn ($id) => (int) $id === $user->id);
            $mensaje = 'Nueva evidencia en la incidencia: '.$nombre;
        } else {
            $destinos = collect([$incidencia->id_usuario])
                ->reject(fn ($id) => (int) $id === $user->id);
            $mensaje = 'Se agregó evidencia a tu incidencia: '.$nombre;
        }

        $usuarios = User::whereIn('id', $destinos)->get();
        Notification::send($usuarios, new IncidenciaNotification('EVIDENCIA', $mensaje, $incidencia->id_incidencia));
    }
}
