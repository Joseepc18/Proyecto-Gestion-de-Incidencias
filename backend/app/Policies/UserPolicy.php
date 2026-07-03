<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    // Editar desde Gestión de usuarios: requiere el permiso y nunca a un usuario normal (esos solo se editan en Mi perfil).
    public function actualizar(User $admin, User $usuario): Response
    {
        return $admin->tienePermiso('usuarios.administrar') && ! $usuario->esNormal()
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
