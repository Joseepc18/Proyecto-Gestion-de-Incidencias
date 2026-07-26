<?php

use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\Notificacion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archiva solas las incidencias resueltas hace más de 24h (sin reapertura pendiente). Corre de verdad
// gracias al contenedor "scheduler" del docker-compose.yml (php artisan schedule:work).
Schedule::command('incidencias:archivar-resueltas')->hourly();

// Sube un nivel de prioridad a las PENDIENTE sin atender hace más de 24h.
Schedule::command('incidencias:escalar-antiguas')->hourly();

// Avisa por correo (una sola vez por hilo) a quien tenga mensajes del chat sin leer hace más de 24h.
Schedule::command('incidencias:recordar-chat-sin-leer')->hourly();

// Email a admin/super_admin con el resumen del día; 7am hora del servidor.
Schedule::command('incidencias:digest-diario')->dailyAt('07:00');

// Limpieza nativa de Laravel: tokens de Sanctum vencidos hace más de 24h.
Schedule::command('sanctum:prune-expired', ['--hours' => 24])->daily();

// Limpieza nativa de Laravel: trabajos en cola que fallaron hace más de 7 días (tabla failed_jobs).
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();

// Limpieza nativa de Laravel: cada modelo trae su propio criterio en su método prunable(). Notificaciones
// leídas hace +30 días, papelera de incidencias con +30 días en soft-delete y bitácora de errores de +90 días.
Schedule::command('model:prune', ['--model' => [Notificacion::class, Incidencia::class, BitacoraError::class]])->daily();
