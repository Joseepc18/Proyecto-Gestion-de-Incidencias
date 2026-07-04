<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // App stateless (sin sesión web): el Gate no recibe $user, la protección real es el
        // Basic Auth de Nginx en /pulse (igual que /horizon). Se abre aquí para no dar 403 detrás de ese borde.
        Gate::define('viewPulse', fn ($user = null) => true);
    }
}
