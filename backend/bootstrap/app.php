<?php

use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\ForceJsonResponse;
use App\Models\BitacoraError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Le indicamos a Laravel que confíe en todos los proxies (Cloudflare)
        $middleware->trustProxies(at: '*');

        // Toda la API se trata como JSON (sin token → 401 limpio, no 500).
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'admin' => CheckAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Respaldo: cualquier error en /api/* se renderiza como JSON (no HTML).
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $throwable) => $request->is('api/*') || $request->expectsJson()
        );

        // Registro centralizado en bitacora_errores (todos los controllers); solo errores de servidor: se ignoran validación/auth/HTTP.
        $exceptions->report(function (Throwable $e) {
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof HttpExceptionInterface) {
                return;
            }

            try {
                BitacoraError::create([
                    'id_usuario' => auth()->id(),
                    'tipo_error' => $e instanceof QueryException ? 'BASE_DATOS' : 'SERVIDOR',
                    'descripcion_error' => substr(get_class($e).': '.$e->getMessage(), 0, 1000),
                ]);
            } catch (Throwable $ignorado) {
                // El logging nunca debe romper la respuesta al usuario.
            }
        });
    })->create();
