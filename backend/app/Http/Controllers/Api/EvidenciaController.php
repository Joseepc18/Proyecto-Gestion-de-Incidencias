<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubirEvidenciaRequest;
use App\Models\Evidencia;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (3 de REPORTE, 1 de RESOLUCION).
    public function subir(SubirEvidenciaRequest $request, Incidencia $incidencia)
    {
        $this->authorize('subirEvidencia', $incidencia);

        $user = $request->user();
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
        $this->authorize('eliminar', $evidencia);

        Storage::disk('public')->delete($evidencia->url_evidencia);
        $evidencia->delete();

        return response()->json(['message' => 'Foto eliminada']);
    }
}
