<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermiso
{
    /**
     * Bloquea la ruta si el usuario autenticado no tiene el permiso indicado.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tienePermiso($permiso)) {
            // La API responde JSON (Accept forzado).
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
