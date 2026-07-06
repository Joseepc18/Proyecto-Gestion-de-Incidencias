<?php

namespace App\Policies\Concerns;

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// Regla compartida del candado: un admin solo gestiona (asignar, quitar, editar, cambiar estado,
// borrar evidencia) la incidencia que él mismo reclamó. El reclamo es el candado.
trait AutorizaReclamo
{
    protected function esDuenoDelReclamo(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->id_admin_atiende === null) {
            return Response::deny('Reclama la incidencia antes de gestionarla.');
        }

        return $incidencia->id_admin_atiende === $user->id
            ? Response::allow()
            : Response::deny('Otro administrador está atendiendo esta incidencia.');
    }
}
