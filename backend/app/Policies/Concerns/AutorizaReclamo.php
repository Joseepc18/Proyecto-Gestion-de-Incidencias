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
        if ($this->reportoLaIncidencia($user, $incidencia)) {
            return Response::deny('No puedes gestionar una incidencia que tú mismo reportaste.');
        }

        if ($incidencia->id_admin_atiende === null) {
            return Response::deny('Reclama la incidencia antes de gestionarla.');
        }

        return $incidencia->id_admin_atiende === $user->id
            ? Response::allow()
            : Response::deny('Otro administrador está atendiendo esta incidencia.');
    }

    // Conflicto de interés: quien reporta una incidencia no la gestiona, aunque tenga el permiso.
    // Solo puede darse si a un rol gestor se le activa incidencias.crear desde el panel.
    protected function reportoLaIncidencia(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->id_usuario === $user->id;
    }
}
