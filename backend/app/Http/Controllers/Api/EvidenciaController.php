<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubirEvidenciaRequest;
use App\Models\Evidencia;
use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (3 de REPORTE, 1 de RESOLUCION).
    public function subir(SubirEvidenciaRequest $request, Incidencia $incidencia)
    {
        // La autorización (admin, autor o técnico asignado) la resuelve el FormRequest.
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

        // #8/#9 — Aviso de evidencia añadida (aquí y no en trigger: solo al agregar fotos después, no las iniciales).
        $this->notificarEvidencia($incidencia, $user);

        return response()->json($incidencia->load('evidencias'));
    }

    // Notifica según quién sube la foto (nunca al actor): el ciudadano → admins+técnicos; el técnico/admin → reportador.
    private function notificarEvidencia(Incidencia $incidencia, User $user): void
    {
        $nombre = $incidencia->nombre_incidencia;

        if ($user->id === $incidencia->id_usuario) {
            $destinos = User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'admin'))
                ->pluck('id')
                ->merge($incidencia->asignaciones()->pluck('id_usuario'))
                ->unique()
                ->reject(fn ($id) => (int) $id === $user->id);
            $mensaje = 'Nueva evidencia en la incidencia: '.$nombre;
        } else {
            $destinos = collect([$incidencia->id_usuario])
                ->reject(fn ($id) => (int) $id === $user->id);
            $mensaje = 'Se agregó evidencia a tu incidencia: '.$nombre;
        }

        foreach ($destinos as $idDestino) {
            Notificacion::create([
                'id_usuario' => $idDestino,
                'id_incidencia' => $incidencia->id_incidencia,
                'tipo_notificacion' => 'EVIDENCIA',
                'mensaje_notificacion' => $mensaje,
            ]);
        }
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
