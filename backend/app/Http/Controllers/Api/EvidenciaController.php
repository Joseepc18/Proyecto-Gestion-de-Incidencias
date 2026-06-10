<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidencia;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (máximo 5 en total).
    public function subir(Request $request, Incidencia $incidencia)
    {
        // Permiso: admin o autor de la incidencia
        $user = $request->user();
        $esAdmin = $user->rol && $user->rol->nombre_rol === 'admin';
        if (! $esAdmin && $incidencia->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // No pasar de 5 fotos en total (las que ya hay + las nuevas)
        if ($incidencia->evidencias()->count() + count($request->file('fotos')) > 5) {
            return response()->json(['message' => 'Máximo 5 fotos por incidencia'], 422);
        }

        foreach ($request->file('fotos') as $foto) {
            $incidencia->evidencias()->create([
                'url_evidencia' => $foto->store('incidencias', 'public'),
                'id_usuario' => $user->id,
            ]);
        }

        return response()->json($incidencia->load('evidencias'));
    }

    // Eliminar una foto (evidencia).
    public function eliminar(Request $request, Evidencia $evidencia)
    {
        // Permiso: admin o autor de la incidencia a la que pertenece la foto
        $user = $request->user();
        $esAdmin = $user->rol && $user->rol->nombre_rol === 'admin';
        if (! $esAdmin && $evidencia->incidencia->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        Storage::disk('public')->delete($evidencia->url_evidencia);
        $evidencia->delete();

        return response()->json(['message' => 'Foto eliminada']);
    }
}
