<?php

namespace App\Http\Controllers;

use App\Concerns\NotificaSinRomper;
use App\Exceptions\AlmacenamientoException;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaDetalleNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    // Habilita $this->authorize(...) en todos los controllers (usa las Policies).
    use AuthorizesRequests;
    use NotificaSinRomper;

    // Lee per_page del request acotado a [1, 100] para evitar listados sin límite.
    protected function perPage(Request $request, int $porDefecto = 10): int
    {
        return min(max((int) $request->input('per_page', $porDefecto), 1), 100);
    }

    // Cierre uniforme de los catch: corre la limpieza opcional, mapea la excepción a su tipo_error, la bitacoriza y responde 500 según el tipo ($mensajes: clave del tipo o 'default').
    protected function errorControlado(\Throwable $e, ?User $actor, string $contexto, array $mensajes, ?callable $limpieza = null): JsonResponse
    {
        if ($limpieza) {
            $limpieza();
        }

        $tipo = match (true) {
            $e instanceof AlmacenamientoException => 'ARCHIVO',
            $e instanceof QueryException => 'BASE_DATOS',
            default => 'SERVIDOR',
        };

        BitacoraError::registrar($actor, $tipo, $contexto, $e->getMessage(), $e);

        return response()->json(['message' => $mensajes[$tipo] ?? $mensajes['default']], 500);
    }

    // Hito idempotente: se llama tras cada acción de gestión (reclamar, asignar, prioridad, estado). Si la incidencia
    // ya reúne las 4 condiciones, manda al ciudadano UN correo de detalle. El UPDATE atómico false→true garantiza
    // que solo la request que gana la carrera lo dispare, una sola vez y sin importar el orden de las acciones.
    protected function enviarCorreoDetalleSiListo(Incidencia $incidencia): void
    {
        $incidencia->refresh();

        if (! $incidencia->estaListaParaCorreoDetalle()) {
            return;
        }

        $marcado = Incidencia::where('id_incidencia', $incidencia->id_incidencia)
            ->where('correo_detalle_enviado', false)
            ->update(['correo_detalle_enviado' => true]);

        if ($marcado === 0) {
            return;
        }

        $reportador = User::find($incidencia->id_usuario);
        if ($reportador === null) {
            return;
        }

        $this->notificarSinRomper(
            fn () => $reportador->notify(new IncidenciaDetalleNotification($incidencia->id_incidencia)),
            $reportador,
            'Controller@enviarCorreoDetalleSiListo'
        );
    }
}
