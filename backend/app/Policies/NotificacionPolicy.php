<?php

namespace App\Policies;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificacionPolicy
{
    // Marcar como leída: solo el dueño de la notificación.
    public function marcar(User $user, Notificacion $notificacion): Response
    {
        return $notificacion->id_usuario === $user->id
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
