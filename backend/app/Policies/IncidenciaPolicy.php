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

    // Editar los detalles: solo admin o el autor mientras esté PENDIENTE; en RESUELTO es de solo lectura para todos.
    public function actualizar(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->estado_incidencia === EstadoIncidencia::Resuelto->value) {
            return Response::deny('La incidencia está resuelta; no se puede editar.');
        }

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

    // Cambiar de estado: admin o el técnico RESPONSABLE (el de APOYO no cambia estados; la transición la valida el controller).
    public function cambiarEstado(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Subir evidencias: autor (REPORTE) o técnico RESPONSABLE (RESOLUCION); en RESUELTO nadie sube (pedir reapertura).
    public function subirEvidencia(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->estado_incidencia === EstadoIncidencia::Resuelto->value) {
            return Response::deny('No se pueden agregar evidencias a una incidencia resuelta.');
        }

        return ($incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia))
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Ver el chat (lectura): reportador, admin y técnico RESPONSABLE; sigue permitido en RESUELTO (se conserva el historial).
    public function verChat(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Escribir en el chat: mismos roles que verChat, pero NO en RESUELTO (solo lectura).
    public function comentar(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->estado_incidencia === EstadoIncidencia::Resuelto->value) {
            return Response::deny('La incidencia está resuelta; el chat es solo de lectura.');
        }

        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Solicitar reapertura: solo el reportador y solo si ya está RESUELTO.
    public function solicitarReapertura(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->id_usuario !== $user->id) {
            return Response::deny('No autorizado');
        }

        return $incidencia->estado_incidencia === EstadoIncidencia::Resuelto->value
            ? Response::allow()
            : Response::deny('Solo puedes solicitar la reapertura de una incidencia ya resuelta.');
    }

    // El técnico está asignado a la incidencia (responsable o de apoyo).
    private function esAsignado(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $user->id)
            ->exists();
    }
}
