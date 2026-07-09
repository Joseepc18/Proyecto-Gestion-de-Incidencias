<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Notifications\DatabaseNotification;

// Mismo modelo/tabla que usa el trait Notifiable (notifications); solo agrega Prunable
// para que "php artisan model:prune" borre las notificaciones leídas hace más de 30 días.
class Notificacion extends DatabaseNotification
{
    use Prunable;

    protected function prunable()
    {
        return static::whereNotNull('read_at')->where('read_at', '<=', now()->subDays(30));
    }
}
