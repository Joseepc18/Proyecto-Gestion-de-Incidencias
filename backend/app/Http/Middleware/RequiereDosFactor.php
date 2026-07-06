<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiereDosFactor
{
    /**
     * Bloquea la acción si el usuario tiene un rol privilegiado (admin/super_admin) y aún no activó el 2FA.
     * El login los deja entrar igual para que puedan llegar a la pantalla de configuración; aquí se exige en las acciones sensibles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->esAdmin() && ! $user->hasEnabledTwoFactorAuthentication()) {
            // La API responde JSON con la bandera two_factor_required.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu rol requiere activar la verificación en dos pasos para realizar esta acción.',
                    'two_factor_required' => true,
                ], 403);
            }

            abort(403, 'Tu rol requiere activar la verificación en dos pasos para realizar esta acción.');
        }

        return $next($request);
    }
}
