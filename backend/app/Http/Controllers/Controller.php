<?php

namespace App\Http\Controllers;

use App\Exceptions\AlmacenamientoException;
use App\Models\BitacoraError;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    // Habilita $this->authorize(...) en todos los controllers (usa las Policies).
    use AuthorizesRequests;

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

    // Ejecuta el envío de notificaciones sin dejar que un fallo rompa la respuesta ya commiteada; solo lo bitacoriza.
    protected function notificarSinRomper(callable $accion, ?User $actor, string $contexto): void
    {
        try {
            $accion();
        } catch (\Throwable $e) {
            BitacoraError::registrar($actor, 'SERVIDOR', $contexto, $e->getMessage());
        }
    }
}
