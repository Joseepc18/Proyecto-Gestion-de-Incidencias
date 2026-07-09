<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    // Lista las notificaciones del usuario + cuántas sin leer; el tipo y el mensaje viven en data (json) y se aplanan al formato del front.
    public function listado(Request $request)
    {
        $usuario = $request->user();

        $notificaciones = $usuario->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? null,
                'mensaje' => $n->data['mensaje'] ?? '',
                'id_incidencia' => $n->data['id_incidencia'] ?? null,
                'contador' => $n->data['contador'] ?? 1,
                'estado_lectura' => $n->read_at !== null,
                'created_at' => $n->created_at,
            ]);

        return [
            'notificaciones' => $notificaciones,
            'no_leidas' => $usuario->unreadNotifications()->count(),
        ];
    }

    // Marcar una notificación como leída; findOrFail sobre la relación del usuario impide tocar ajenas.
    public function marcarLeida(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return ['message' => 'Notificación marcada como leída'];
    }

    // Marcar todas las notificaciones del usuario como leídas.
    public function marcarTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return ['message' => 'Todas marcadas como leídas'];
    }
}
