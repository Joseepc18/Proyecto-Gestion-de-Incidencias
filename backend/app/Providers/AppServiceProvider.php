<?php

namespace App\Providers;

use App\Models\AsignacionIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Observers\IncidenciaObserver;
use App\Policies\AsignacionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Usamos solo el backend de 2FA de Fortify (trait + actions); su login/perfil/reset por sesión no aplican a nuestra API por token.
        Fortify::ignoreRoutes();

        // Telescope solo en local: ni sus rutas ni sus watchers se registran en prod (composer.json lo excluye
        // del auto-discovery vía "dont-discover", así que hay que registrarlo a mano aquí).
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Incidencia::observe(IncidenciaObserver::class);

        // Nombre del modelo no calza con la convención de auto-descubrimiento (AsignacionIncidencia).
        Gate::policy(AsignacionIncidencia::class, AsignacionPolicy::class);

        // Gate suelto porque no hay un modelo de por medio (métricas del propio técnico, no de un recurso).
        Gate::define('ver-metricas-tecnico', fn (User $user) => $user->esTecnico());

        $this->registrarLimitesDeIntentos();
    }

    /**
     * Límites de intentos de las rutas de autenticación: al cubo de siempre por IP se le suma
     * uno por objetivo (correo o reto 2FA), para que la fuerza bruta contra una cuenta concreta
     * siga acotada aunque la IP quede mal resuelta.
     */
    private function registrarLimitesDeIntentos(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login-ip:'.$request->ip()),
            Limit::perMinute(10)->by('login-cuenta:'.Str::lower((string) $request->input('email'))),
        ]);

        RateLimiter::for('2fa-challenge', fn (Request $request) => [
            Limit::perMinute(6)->by('2fa-ip:'.$request->ip()),
            // El reto ya identifica al usuario y obtener uno nuevo obliga a pasar otra vez por /login.
            Limit::perMinute(10)->by('2fa-reto:'.hash('sha256', (string) $request->input('challenge_token'))),
        ]);
    }
}
