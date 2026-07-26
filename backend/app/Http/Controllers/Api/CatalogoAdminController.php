<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarSubtipoRequest;
use App\Http\Requests\GuardarTipoRequest;
use App\Models\BitacoraError;
use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

// CRUD de tipos y subtipos de incidencia (solo super_admin; rutas bajo el middleware 'permiso:catalogos.administrar').
class CatalogoAdminController extends Controller
{
    // Tipos

    public function crearTipo(GuardarTipoRequest $request)
    {
        $tipo = TipoIncidencia::create($request->validated());

        return response()->json($tipo, 201);
    }

    public function actualizarTipo(GuardarTipoRequest $request, TipoIncidencia $tipo)
    {
        $tipo->update($request->validated());

        return $tipo;
    }

    public function eliminarTipo(Request $request, TipoIncidencia $tipo)
    {
        if ($tipo->subtipos()->exists()) {
            return response()->json(['message' => 'No puedes eliminar un tipo con subtipos. Elimina primero sus subtipos.'], 422);
        }

        return $this->borrarOAvisarDeLaFk(
            $request,
            fn () => $tipo->delete(),
            'CatalogoAdminController@eliminarTipo',
            'No puedes eliminar este tipo porque algo lo sigue usando.',
            'Tipo eliminado'
        );
    }

    // Subtipos

    public function crearSubtipo(GuardarSubtipoRequest $request)
    {
        $subtipo = SubtipoIncidencia::create($request->validated());

        return response()->json($subtipo->load('tipo'), 201);
    }

    public function actualizarSubtipo(GuardarSubtipoRequest $request, SubtipoIncidencia $subtipo)
    {
        $subtipo->update($request->validated());

        return $subtipo->load('tipo');
    }

    // withTrashed: las incidencias en la papelera siguen apuntando al subtipo y la FK es RESTRICT,
    // así que sin esto el conteo las ignoraba y el DELETE reventaba con un 500 en vez de este 422.
    public function eliminarSubtipo(Request $request, SubtipoIncidencia $subtipo)
    {
        if ($subtipo->incidencias()->withTrashed()->exists()) {
            return response()->json(['message' => 'No puedes eliminar un subtipo con incidencias registradas, incluidas las que estén en la papelera.'], 422);
        }

        return $this->borrarOAvisarDeLaFk(
            $request,
            fn () => $subtipo->delete(),
            'CatalogoAdminController@eliminarSubtipo',
            'No puedes eliminar este subtipo porque algo lo sigue usando.',
            'Subtipo eliminado'
        );
    }

    // Red de seguridad de los dos borrados: si una FK RESTRICT que no comprobamos frena el DELETE,
    // el usuario recibe un 422 explicable en vez del 500 crudo de la QueryException.
    private function borrarOAvisarDeLaFk(Request $request, callable $borrar, string $contexto, string $mensajeFk, string $mensajeOk)
    {
        try {
            $borrar();
        } catch (QueryException $e) {
            BitacoraError::registrar($request->user(), 'BASE_DATOS', $contexto, $e->getMessage(), $e);

            return response()->json(['message' => $mensajeFk], 422);
        }

        return ['message' => $mensajeOk];
    }
}
