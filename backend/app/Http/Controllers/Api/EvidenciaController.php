<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evidencia;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (3 de REPORTE, 1 de RESOLUCION).
    public function subir(Request $request, Incidencia $incidencia)
    {
        // Permiso: admin, autor o técnico asignado
        $user = $request->user();
        $esAdmin = $user->rol && $user->rol->nombre_rol === 'admin';
        $esAutor = $incidencia->id_usuario === $user->id;
        $esTecnicoAsignado = $incidencia->asignaciones()->where('id_usuario', $user->id)->exists();
        if (! $esAdmin && ! $esAutor && ! $esTecnicoAsignado) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'tipo_evidencia' => 'nullable|in:REPORTE,RESOLUCION',
        ]);

        $tipo = $request->input('tipo_evidencia', 'REPORTE');
        $limite = $tipo === 'RESOLUCION' ? 1 : 3;

        // No pasar del límite por tipo (las que ya hay + las nuevas)
        if ($incidencia->evidencias()->where('tipo_evidencia', $tipo)->count() + count($request->file('fotos')) > $limite) {
            return response()->json(['message' => "Máximo $limite foto(s) de tipo $tipo"], 422);
        }

        foreach ($request->file('fotos') as $foto) {
            $incidencia->evidencias()->create([
                'url_evidencia' => $foto->store('incidencias', 'public'),
                'id_usuario' => $user->id,
                'tipo_evidencia' => $tipo,
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
