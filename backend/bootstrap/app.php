<?php

use App\Http\Middleware\AsegurarEmailVerificado;
use App\Http\Middleware\CheckPermiso;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RequiereDosFactor;
use App\Models\BitacoraError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Los proxies confiables NO se fijan aquí: TrustProxies los lee de config/trustedproxy.php
        // en cada petición. Confiar en todos ('*') hacía que Laravel tomara la primera entrada de
        // X-Forwarded-For —la que escribe el cliente— como IP, y con eso se saltaba el throttle.

        // Toda la API se trata como JSON (sin token → 401 limpio, no 500).
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'permiso' => CheckPermiso::class,
            'verificado' => AsegurarEmailVerificado::class,
            '2fa' => RequiereDosFactor::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Respaldo: cualquier error en /api/* se renderiza como JSON (no HTML).
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $throwable) => $request->is('api/*') || $request->expectsJson()
        );

        // Mensajes por defecto de Laravel que vienen en inglés (no pasan por lang/es): se traducen aquí.
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return response()->json(['message' => 'Demasiados intentos. Espera un momento y vuelve a intentar.'], 429);
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'No autorizado.'], 403);
            }
        });

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
                    'id_usuario' => auth('sanctum')->id(),
                    'tipo_error' => $e instanceof QueryException ? 'BASE_DATOS' : 'SERVIDOR',
                    'descripcion_error' => $e instanceof QueryException ? 'Error de base de datos (SQL oculto por seguridad)' : substr(get_class($e).': '.$e->getMessage(), 0, 1000),
                ]);
            } catch (Throwable $ignorado) {
                // El logging nunca debe romper la respuesta al usuario.
            }
        });
    })->create();
