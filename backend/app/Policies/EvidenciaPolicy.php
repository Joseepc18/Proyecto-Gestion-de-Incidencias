<?php

namespace App\Policies;

use App\Models\Evidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenciaPolicy
{
    // Eliminar una foto: admin, autor de la incidencia, o el técnico RESPONSABLE
    // (así puede reemplazar su foto de resolución/avance).
    public function eliminar(User $user, Evidencia $evidencia): Response
    {
        $incidencia = $evidencia->incidencia;

        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
