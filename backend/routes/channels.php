<?php

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canal privado del chat de una incidencia: reusa la policy verChat (admin, dueño o técnico responsable).
Broadcast::channel('incidencia.{incidencia}', function (User $user, Incidencia $incidencia) {
    return $user->can('verChat', $incidencia);
});

// Canal privado de cada usuario para las notificaciones broadcast (campana en vivo, sesión posterior).
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
