<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('incidencia.{id_incidencia}', function ($user, $id_incidencia) {
    // Si es admin, puede ver todo.
    if ($user->esAdmin()) {
        return true;
    }
    
    $incidencia = \App\Models\Incidencia::find($id_incidencia);
    if (!$incidencia) {
        return false;
    }

    // El dueño, o el técnico responsable, o un ayudante pueden ver el chat
    if ($incidencia->id_usuario === $user->id) {
        return true;
    }

    if ($incidencia->asignaciones()->where('id_tecnico', $user->id)->exists()) {
        return true;
    }

    return false;
});
