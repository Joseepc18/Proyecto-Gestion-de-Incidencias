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

// Canal de updates de una incidencia (estado/prioridad/asignaciones/candado): quien puede VER el detalle.
// Va aparte del canal del chat para que el técnico de apoyo reciba los updates sin acceder al chat.
Broadcast::channel('incidencia.updates.{incidencia}', function (User $user, Incidencia $incidencia) {
    return $user->can('ver', $incidencia);
});

// Tablero de presencia de administradores: nuevas incidencias y quién reclamó qué.
// En presence channels se devuelve la info del miembro (o false si no está autorizado).
Broadcast::channel('tablero', function (User $user) {
    return $user->tienePermiso('incidencias.gestionar')
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
