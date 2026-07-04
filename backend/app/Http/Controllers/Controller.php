<?php

namespace App\Http\Controllers;

use App\Models\BitacoraError;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
