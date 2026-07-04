<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarSubtipoRequest;
use App\Http\Requests\GuardarTipoRequest;
use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use Illuminate\Http\Request;

class PanelCatalogoController extends Controller
{
    // Vista única con dos pestañas (tipos / subtipos); los subtipos se pueden filtrar por tipo padre.
    public function index(Request $request)
    {
        $vista = $request->query('vista') === 'subtipos' ? 'subtipos' : 'tipos';
        $filtroTipo = $request->query('tipo', '');

        if ($vista === 'subtipos') {
            $query = SubtipoIncidencia::with('tipo')->orderBy('nombre_subtipo_incidencia');
            if ($filtroTipo !== '') {
                $query->where('id_tipo_incidencia', $filtroTipo);
            }
            $registros = $query->paginate(10)->withQueryString();
        } else {
            $registros = TipoIncidencia::withCount('subtipos')
                ->orderBy('nombre_tipo_incidencia')
                ->paginate(10)
                ->withQueryString();
        }

        return view('panel.catalogos.index', [
            'vista' => $vista,
            'filtroTipo' => $filtroTipo,
            'tipos' => TipoIncidencia::orderBy('nombre_tipo_incidencia')->get(),
            'registros' => $registros,
        ]);
    }

    // Tipos

    public function crearTipo()
    {
        return view('panel.catalogos.tipo-form', ['tipo' => null]);
    }

    public function guardarTipo(GuardarTipoRequest $request)
    {
        TipoIncidencia::create($request->validated());

        return redirect()->route('panel.catalogos')->with('exito', 'Tipo creado');
    }

    public function editarTipo(TipoIncidencia $tipo)
    {
        return view('panel.catalogos.tipo-form', ['tipo' => $tipo]);
    }

    public function actualizarTipo(GuardarTipoRequest $request, TipoIncidencia $tipo)
    {
        $tipo->update($request->validated());

        return redirect()->route('panel.catalogos')->with('exito', 'Tipo actualizado');
    }

    public function eliminarTipo(TipoIncidencia $tipo)
    {
        if ($tipo->subtipos()->exists()) {
            return back()->with('error', 'No puedes eliminar un tipo con subtipos. Elimina primero sus subtipos.');
        }

        $tipo->delete();

        return redirect()->route('panel.catalogos')->with('exito', 'Tipo eliminado');
    }

    // Subtipos

    public function crearSubtipo()
    {
        return view('panel.catalogos.subtipo-form', [
            'subtipo' => null,
            'tipos' => TipoIncidencia::orderBy('nombre_tipo_incidencia')->get(),
        ]);
    }

    public function guardarSubtipo(GuardarSubtipoRequest $request)
    {
        SubtipoIncidencia::create($request->validated());

        return redirect()->route('panel.catalogos', ['vista' => 'subtipos'])->with('exito', 'Subtipo creado');
    }

    public function editarSubtipo(SubtipoIncidencia $subtipo)
    {
        return view('panel.catalogos.subtipo-form', [
            'subtipo' => $subtipo,
            'tipos' => TipoIncidencia::orderBy('nombre_tipo_incidencia')->get(),
        ]);
    }

    public function actualizarSubtipo(GuardarSubtipoRequest $request, SubtipoIncidencia $subtipo)
    {
        $subtipo->update($request->validated());

        return redirect()->route('panel.catalogos', ['vista' => 'subtipos'])->with('exito', 'Subtipo actualizado');
    }

    public function eliminarSubtipo(SubtipoIncidencia $subtipo)
    {
        if ($subtipo->incidencias()->exists()) {
            return back()->with('error', 'No puedes eliminar un subtipo con incidencias registradas.');
        }

        $subtipo->delete();

        return redirect()->route('panel.catalogos', ['vista' => 'subtipos'])->with('exito', 'Subtipo eliminado');
    }
}
