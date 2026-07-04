<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoEvidencia;
use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirEvidenciaRequest;
use App\Models\BitacoraError;
use App\Models\Evidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (hasta 3 de REPORTE y hasta 3 de RESOLUCION).
    public function subir(SubirEvidenciaRequest $request, Incidencia $incidencia)
    {
        $user = $request->user();
        $tipo = $request->input('tipo_evidencia', TipoEvidencia::Reporte->value);
        $limite = Incidencia::LIMITE_EVIDENCIAS_POR_TIPO;

        if ($incidencia->evidencias()->where('tipo_evidencia', $tipo)->count() + count($request->file('fotos')) > $limite) {
            return response()->json(['message' => "Máximo $limite foto(s) de tipo $tipo"], 422);
        }

        $rutasGuardadas = [];

        try {
            DB::transaction(function () use ($request, $incidencia, $user, $tipo, &$rutasGuardadas) {
                $incidencia->guardarEvidencias($request->file('fotos'), $user->id, $tipo, $rutasGuardadas);
            });
        } catch (AlmacenamientoException $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'ARCHIVO', 'EvidenciaController@subir', $e->getMessage());

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (QueryException $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'BASE_DATOS', 'EvidenciaController@subir', $e->getMessage(), $e);

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'SERVIDOR', 'EvidenciaController@subir', $e->getMessage());

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        }

        // La subida ya está commiteada: si la notificación falla, se bitácoriza pero NO rompe la respuesta.
        $this->notificarSinRomper(
            fn () => $this->notificarEvidencia($incidencia, $user),
            $user,
            'EvidenciaController@subir (notificación)'
        );

        return $incidencia->load('evidencias');
    }

    // Notifica según quién sube la foto (nunca al actor): el ciudadano → admins+técnicos; el técnico/admin → reportador.
    private function notificarEvidencia(Incidencia $incidencia, User $user): void
    {
        $nombre = $incidencia->nombre_incidencia;

        if ($user->id === $incidencia->id_usuario) {
            $destinos = User::conPermiso('incidencias.gestionar')
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

        $usuarios = User::whereIn('id', $destinos)->get();
        Notification::send($usuarios, new IncidenciaNotification('EVIDENCIA', $mensaje, $incidencia->id_incidencia));
    }

    // Eliminar una foto (evidencia).
    public function eliminar(Request $request, Evidencia $evidencia)
    {
        $this->authorize('eliminar', $evidencia);

        Storage::disk('public')->delete($evidencia->url_evidencia);
        $evidencia->delete();

        return ['message' => 'Foto eliminada'];
    }
}
