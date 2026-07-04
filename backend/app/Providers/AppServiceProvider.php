<?php

namespace App\Providers;

use App\Models\AsignacionIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Observers\IncidenciaObserver;
use App\Policies\AsignacionPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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

        // La paginación nativa de Blade ($paginator->links()) usa Bootstrap 5, no Tailwind.
        Paginator::useBootstrapFive();

        Incidencia::observe(IncidenciaObserver::class);

        // Nombre del modelo no calza con la convención de auto-descubrimiento (AsignacionIncidencia).
        Gate::policy(AsignacionIncidencia::class, AsignacionPolicy::class);

        // Gate suelto porque no hay un modelo de por medio (métricas del propio técnico, no de un recurso).
        Gate::define('ver-metricas-tecnico', fn (User $user) => $user->esTecnico());
    }
}
