<?php

namespace App\Policies;

use App\Models\Evidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenciaPolicy
{
    // Eliminar una foto: admin o autor de la incidencia a la que pertenece.
    public function eliminar(User $user, Evidencia $evidencia): Response
    {
        return $user->esAdmin() || $evidencia->incidencia->id_usuario === $user->id
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
