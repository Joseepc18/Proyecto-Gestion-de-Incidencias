<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archiva solas las incidencias resueltas hace más de 24h (sin reapertura pendiente). Corre de verdad
// gracias al contenedor "scheduler" del docker-compose.yml (php artisan schedule:work).
Schedule::command('incidencias:archivar-resueltas')->hourly();
