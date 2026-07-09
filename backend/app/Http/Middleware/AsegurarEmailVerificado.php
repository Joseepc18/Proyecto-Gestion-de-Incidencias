<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarEmailVerificado
{
    /**
     * Bloquea la ruta si el usuario aún no verificó su correo (respaldo del aviso del frontend).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Verifica tu correo electrónico para continuar.',
            ], 403);
        }

        return $next($request);
    }
}
