<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ComentarioPolicy
{
    // Editar un comentario: solo su autor y solo si ahora mismo podría escribir en ese chat.
    // El segundo corte se delega en IncidenciaPolicy::comentar en vez de replicarlo, que es como se
    // desincronizó antes: comentar() ganó reglas (candado del supervisor) y esta copia no se enteró.
    public function actualizar(User $user, Comentario $comentario): Response
    {
        if ($comentario->id_usuario !== $user->id) {
            return Response::deny('No autorizado');
        }

        return Gate::forUser($user)->inspect('comentar', $comentario->incidencia);
    }
}
