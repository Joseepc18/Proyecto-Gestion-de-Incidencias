<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BitacoraError;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    // Listar las notificaciones del usuario autenticado + cuántas sin leer.
    public function listado(Request $request)
    {
        $usuario = $request->user();

        $notificaciones = Notificacion::where('id_usuario', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $noLeidas = Notificacion::where('id_usuario', $usuario->id)
            ->where('estado_lectura', false)
            ->count();

        return [
            'notificaciones' => $notificaciones,
            'no_leidas' => $noLeidas,
        ];
    }

    // Marcar una notificación como leída (solo si es del usuario).
    public function marcarLeida(Request $request, Notificacion $notificacion)
    {
        $this->authorize('marcar', $notificacion);

        try {
            $notificacion->update([
                'estado_lectura' => true,
                'fecha_lectura' => now(),
            ]);

            return ['message' => 'Notificación marcada como leída'];
        } catch (\Exception $e) {
            BitacoraError::registrar($request->user(), 'BASE_DATOS', 'NotificacionController@marcarLeida', $e->getMessage());

            return response()->json(['message' => 'Error al actualizar la notificación'], 500);
        }
    }

    // Marcar todas las notificaciones del usuario como leídas.
    public function marcarTodas(Request $request)
    {
        try {
            Notificacion::where('id_usuario', $request->user()->id)
                ->where('estado_lectura', false)
                ->update([
                    'estado_lectura' => true,
                    'fecha_lectura' => now(),
                ]);

            return ['message' => 'Todas marcadas como leídas'];
        } catch (\Exception $e) {
            BitacoraError::registrar($request->user(), 'BASE_DATOS', 'NotificacionController@marcarTodas', $e->getMessage());

            return response()->json(['message' => 'Error al actualizar las notificaciones'], 500);
        }
    }
}
