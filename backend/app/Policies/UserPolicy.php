<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    // Editar desde Gestión de usuarios: requiere el permiso y nunca a un usuario normal (esos solo se editan en Mi perfil).
    public function actualizar(User $admin, User $usuario): Response
    {
        // El formulario obliga a elegir rol tecnico|admin, así que editarse aquí es degradarse y dejar el sistema sin administrador.
        if ($admin->id === $usuario->id) {
            return Response::deny('No puedes editar tu propia cuenta desde aquí. Edita tus propios datos desde Mi perfil.');
        }

        // Misma razón: nadie degrada a un Administrador del Sistema desde la gestión.
        if ($usuario->esSuperAdmin()) {
            return Response::deny('No se puede editar a un Administrador del Sistema desde aquí.');
        }

        return $admin->tienePermiso('usuarios.administrar') && ! $usuario->esNormal()
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Restablecer el 2FA de OTRO usuario: solo super_admin y nunca sobre sí mismo (el propio se desactiva desde Mi perfil con código).
    public function reiniciarDosFactor(User $admin, User $usuario): Response
    {
        if ($admin->id === $usuario->id) {
            return Response::deny('No puedes restablecer tu propio 2FA desde aquí.');
        }

        return $admin->esSuperAdmin()
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
