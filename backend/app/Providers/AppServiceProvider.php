<?php

namespace App\Providers;

use App\Models\Incidencia;
use App\Observers\IncidenciaObserver;
use Illuminate\Http\Resources\Json\JsonResource;
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
    }
}
