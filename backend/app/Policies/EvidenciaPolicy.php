<?php

namespace App\Policies;

use App\Models\Evidencia;
use App\Models\User;
use App\Policies\Concerns\AutorizaReclamo;
use Illuminate\Auth\Access\Response;

class EvidenciaPolicy
{
    use AutorizaReclamo;

    // Eliminar una foto: autor o técnico RESPONSABLE siempre; un admin solo con el permiso de gestión
    // y siendo dueño del reclamo (mismo candado que asignar). El super_admin view-only queda fuera.
    // En RESUELTO/CERRADO nadie borra (expediente cerrado).
    public function eliminar(User $user, Evidencia $evidencia): Response
    {
        $incidencia = $evidencia->incidencia;

        if ($incidencia->esTerminal()) {
            return Response::deny('No se pueden eliminar evidencias de una incidencia resuelta.');
        }

        if ($incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia)) {
            return Response::allow();
        }

        if ($user->tienePermiso('incidencias.gestionar')) {
            return $this->esDuenoDelReclamo($user, $incidencia);
        }

        return Response::deny('No autorizado');
    }
}
