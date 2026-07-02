<?php

namespace App\Policies;

use App\Models\AsignacionIncidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AsignacionPolicy
{
    // Quitar una asignación: solo admin (el estado RESUELTO ya lo valida el controller con su propio 422).
    public function quitar(User $user, AsignacionIncidencia $asignacion): Response
    {
        return $user->esAdmin() ? Response::allow() : Response::deny('No autorizado');
    }
}
