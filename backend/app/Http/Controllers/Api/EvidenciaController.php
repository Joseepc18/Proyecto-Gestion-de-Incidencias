<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoEvidencia;
use App\Events\EvidenciaSubida;
use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirEvidenciaRequest;
use App\Models\BitacoraError;
use App\Models\Evidencia;
use App\Models\Incidencia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    // Agregar fotos a una incidencia (hasta 3 de REPORTE y hasta 3 de RESOLUCION).
    public function subir(SubirEvidenciaRequest $request, Incidencia $incidencia)
    {
        $user = $request->user();
        // El tipo lo decide el rol, no el cliente: el técnico RESPONSABLE sube RESOLUCION; el autor, REPORTE.
        $tipo = $user->esResponsableDe($incidencia)
            ? TipoEvidencia::Resolucion->value
            : TipoEvidencia::Reporte->value;
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
            Storage::disk('evidencias')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'ARCHIVO', 'EvidenciaController@subir', $e->getMessage());

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (QueryException $e) {
            Storage::disk('evidencias')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'BASE_DATOS', 'EvidenciaController@subir', $e->getMessage(), $e);

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (\Throwable $e) {
            Storage::disk('evidencias')->delete($rutasGuardadas);
            BitacoraError::registrar($user, 'SERVIDOR', 'EvidenciaController@subir', $e->getMessage());

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        }

        // La subida ya está commiteada: si la notificación falla, se bitácoriza pero NO rompe la respuesta.
        $this->notificarSinRomper(
            fn () => event(new EvidenciaSubida($incidencia, $user)),
            $user,
            'EvidenciaController@subir (notificación)'
        );

        return $incidencia->load('evidencias');
    }

    // Eliminar una foto (evidencia).
    public function eliminar(Request $request, Evidencia $evidencia)
    {
        $this->authorize('eliminar', $evidencia);

        $url = $evidencia->url_evidencia;
        $evidencia->delete();
        Storage::disk('evidencias')->delete($url);

        return ['message' => 'Foto eliminada'];
    }

    // Sirve el archivo del disco privado; protegida por firma (middleware signed) porque el <img> no manda token: la autorización real ocurrió al generar la URL firmada.
    public function archivo(Evidencia $evidencia)
    {
        $disco = Storage::disk('evidencias');

        if (! $disco->exists($evidencia->url_evidencia)) {
            abort(404);
        }

        return $disco->response($evidencia->url_evidencia);
    }
}
