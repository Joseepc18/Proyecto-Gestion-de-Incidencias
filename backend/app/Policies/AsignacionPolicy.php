<?php

namespace App\Policies;

use App\Models\AsignacionIncidencia;
use App\Models\User;
use App\Policies\Concerns\AutorizaReclamo;
use Illuminate\Auth\Access\Response;

class AsignacionPolicy
{
    use AutorizaReclamo;

    // Quitar una asignación: mismo candado que asignar → el admin dueño del reclamo (el estado RESUELTO ya lo valida el controller con su 422).
    public function quitar(User $user, AsignacionIncidencia $asignacion): Response
    {
        if (! $user->tienePermiso('incidencias.gestionar')) {
            return Response::deny('No autorizado');
        }

        return $this->esDuenoDelReclamo($user, $asignacion->incidencia);
    }
}
