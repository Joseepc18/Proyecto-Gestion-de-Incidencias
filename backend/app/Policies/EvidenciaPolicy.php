<?php

namespace App\Policies;

use App\Enums\EstadoIncidencia;
use App\Models\Evidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EvidenciaPolicy
{
    // Eliminar una foto: admin, autor de la incidencia, o el técnico RESPONSABLE
    // (así puede reemplazar su foto de resolución/avance). En RESUELTO nadie borra:
    // las evidencias quedan como expediente cerrado (reapertura debe pasar primero).
    public function eliminar(User $user, Evidencia $evidencia): Response
    {
        $incidencia = $evidencia->incidencia;

        if ($incidencia->estado_incidencia === EstadoIncidencia::Resuelto->value) {
            return Response::deny('No se pueden eliminar evidencias de una incidencia resuelta.');
        }

        return ($user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia))
            ? Response::allow()
            : Response::deny('No autorizado');
    }
}
