<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarSubtipoRequest;
use App\Http\Requests\GuardarTipoRequest;
use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use Illuminate\Support\Facades\Cache;

// CRUD de tipos y subtipos de incidencia (solo admin; rutas bajo el middleware 'admin').
class CatalogoAdminController extends Controller
{
    // Tipos

    public function crearTipo(GuardarTipoRequest $request)
    {
        $tipo = TipoIncidencia::create($request->validated());

        Cache::forget('catalogo_tipos_incidencia');

        return response()->json($tipo, 201);
    }

    public function actualizarTipo(GuardarTipoRequest $request, TipoIncidencia $tipo)
    {
        $tipo->update($request->validated());

        Cache::forget('catalogo_tipos_incidencia');

        return $tipo;
    }

    public function eliminarTipo(TipoIncidencia $tipo)
    {
        if ($tipo->subtipos()->exists()) {
            return response()->json(['message' => 'No puedes eliminar un tipo con subtipos. Elimina primero sus subtipos.'], 422);
        }

        $tipo->delete();

        Cache::forget('catalogo_tipos_incidencia');

        return ['message' => 'Tipo eliminado'];
    }

    // Subtipos

    public function crearSubtipo(GuardarSubtipoRequest $request)
    {
        $subtipo = SubtipoIncidencia::create($request->validated());

        Cache::forget('catalogo_tipos_incidencia');

        return response()->json($subtipo->load('tipo'), 201);
    }

    public function actualizarSubtipo(GuardarSubtipoRequest $request, SubtipoIncidencia $subtipo)
    {
        $subtipo->update($request->validated());

        Cache::forget('catalogo_tipos_incidencia');

        return $subtipo->load('tipo');
    }

    public function eliminarSubtipo(SubtipoIncidencia $subtipo)
    {
        if ($subtipo->incidencias()->exists()) {
            return response()->json(['message' => 'No puedes eliminar un subtipo con incidencias registradas.'], 422);
        }

        $subtipo->delete();

        Cache::forget('catalogo_tipos_incidencia');

        return ['message' => 'Subtipo eliminado'];
    }
}
