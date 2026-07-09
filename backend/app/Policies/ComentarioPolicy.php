<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComentarioPolicy
{
    // Editar un comentario: solo su autor, y no si la incidencia ya quedó RESUELTA/CERRADA (mismo corte que comentar()).
    public function actualizar(User $user, Comentario $comentario): Response
    {
        if ($comentario->id_usuario !== $user->id) {
            return Response::deny('No autorizado');
        }

        return $comentario->incidencia->esTerminal()
            ? Response::deny('La incidencia está resuelta; el chat es solo de lectura.')
            : Response::allow();
    }
}
