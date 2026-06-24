<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Fuerza que toda petición a la API se trate como JSON. Así, sin token, el
// middleware de autenticación devuelve un 401 limpio en vez de intentar
// redirigir a la ruta web 'login' (que no existe en una API por token) → 500.
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
