<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComentarioPolicy
{
    // Editar un comentario: solo su autor.
    public function actualizar(User $user, Comentario $comentario): Response
    {
        return $comentario->id_usuario === $user->id
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
