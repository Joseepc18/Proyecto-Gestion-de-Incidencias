<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Fuerza JSON en toda la API: sin token, el auth devuelve un 401 limpio en vez de redirigir a 'login' (web) y dar 500.
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
