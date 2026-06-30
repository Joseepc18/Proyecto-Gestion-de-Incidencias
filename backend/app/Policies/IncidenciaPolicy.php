<?php

namespace App\Policies;

use App\Enums\EstadoIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IncidenciaPolicy
{
    // Ver una incidencia: el admin ve todas; el ciudadano las suyas; el técnico solo las que tiene asignadas.
    public function ver(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin() || $incidencia->id_usuario === $user->id) {
            return Response::allow();
        }

        // El técnico (responsable o de apoyo) solo ve las incidencias donde está asignado.
        if ($user->esTecnico() && $this->esAsignado($user, $incidencia)) {
            return Response::allow();
        }

        return Response::deny('No autorizado');
    }

    // El historial sigue las mismas reglas que ver el detalle.
    public function verHistorial(User $user, Incidencia $incidencia): Response
    {
        return $this->ver($user, $incidencia);
    }

    // Editar los detalles: solo admin o el autor mientras esté PENDIENTE.
    // Los técnicos (responsable incluido) NO editan los detalles de la incidencia.
    public function actualizar(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin()) {
            return Response::allow();
        }

        if ($incidencia->id_usuario === $user->id) {
            return $incidencia->estado_incidencia === EstadoIncidencia::Pendiente->value
                ? Response::allow()
                : Response::deny('No puedes editar esta incidencia porque ya está en proceso. Usa los comentarios para comunicarte con el equipo.');
        }

        return Response::deny('No autorizado');
    }

    // Eliminar: admin (siempre) o autor (solo mientras esté PENDIENTE, para no perder trazabilidad).
    public function eliminar(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin()) {
            return Response::allow();
        }

        if ($incidencia->id_usuario === $user->id) {
            return $incidencia->estado_incidencia === EstadoIncidencia::Pendiente->value
                ? Response::allow()
                : Response::deny('No puedes eliminar esta incidencia porque ya está en proceso o resuelta.');
        }

        return Response::deny('No autorizado');
    }

    // Cambiar de estado: admin (libre) o el técnico RESPONSABLE (la transición la valida el controller).
    // El técnico de APOYO no cambia estados (solo puede ver el detalle).
    public function cambiarEstado(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Subir evidencias: autor (fotos de REPORTE) o el técnico RESPONSABLE (foto de RESOLUCION).
    // El administrador y el técnico de APOYO no suben evidencias.
    public function subirEvidencia(User $user, Incidencia $incidencia): Response
    {
        return $incidencia->id_usuario === $user->id
        || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Ver/escribir el chat: reportador, admin y técnico RESPONSABLE (el apoyo queda fuera).
    public function verChat(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // El técnico está asignado a la incidencia (responsable o de apoyo).
    private function esAsignado(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $user->id)
            ->exists();
    }
}
