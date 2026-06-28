<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirEvidenciaRequest;
use App\Models\BitacoraError;
use App\Models\Evidencia;
use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (hasta 3 de REPORTE y hasta 3 de RESOLUCION).
    public function subir(SubirEvidenciaRequest $request, Incidencia $incidencia)
    {
        // La autorización (autor para REPORTE o técnico responsable para RESOLUCION) la resuelve el FormRequest.
        $user = $request->user();
        $tipo = $request->input('tipo_evidencia', 'REPORTE');
        $limite = 3;

        // No pasar del límite por tipo (las que ya hay + las nuevas)
        if ($incidencia->evidencias()->where('tipo_evidencia', $tipo)->count() + count($request->file('fotos')) > $limite) {
            return response()->json(['message' => "Máximo $limite foto(s) de tipo $tipo"], 422);
        }

        // Rutas ya escritas al disco; si la transacción falla las borramos a mano (el rollback no toca el disco).
        $rutasGuardadas = [];

        try {
            // Todo o nada: si una foto no se guarda, no queda ninguna a medias.
            DB::transaction(function () use ($request, $incidencia, $user, $tipo, &$rutasGuardadas) {
                foreach ($request->file('fotos') as $foto) {
                    $ruta = $foto->store('incidencias', 'public');
                    // store() devuelve false si la escritura falla (permisos/disco): no creamos evidencia fantasma
                    if ($ruta === false) {
                        throw new AlmacenamientoException('No se pudo guardar la foto en el disco');
                    }
                    $rutasGuardadas[] = $ruta;
                    $incidencia->evidencias()->create([
                        'url_evidencia' => $ruta,
                        'id_usuario' => $user->id,
                        'tipo_evidencia' => $tipo,
                    ]);
                }
            });
        } catch (AlmacenamientoException $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::create([
                'id_usuario' => $user->id,
                'tipo_error' => 'ARCHIVO',
                'descripcion_error' => 'EvidenciaController@subir: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (\Throwable $e) {
            // Si la transacción falla por otra causa (p.ej. el trigger de límite), limpiamos las fotos ya escritas y dejamos que el handler global registre.
            Storage::disk('public')->delete($rutasGuardadas);
            throw $e;
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
