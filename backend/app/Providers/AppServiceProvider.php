<?php

namespace App\Providers;

use App\Models\AsignacionIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Observers\IncidenciaObserver;
use App\Policies\AsignacionPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }
}
