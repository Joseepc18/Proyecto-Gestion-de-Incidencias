<?php

namespace App\Policies;

use App\Models\Evidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenciaPolicy
{
    // Eliminar una foto: admin, autor o técnico RESPONSABLE; en RESUELTO nadie borra (expediente cerrado).
    public function eliminar(User $user, Evidencia $evidencia): Response
    {
        $incidencia = $evidencia->incidencia;

        if ($incidencia->estaResuelta()) {
            return Response::deny('No se pueden eliminar evidencias de una incidencia resuelta.');
        }

        return ($user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia))
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
