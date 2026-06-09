<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    // Listar incidencias con filtros opcionales, paginadas
    public function listadoIncidencias(Request $request)
    {
        $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionales (solo si vienen en la petición)
        if ($request->filled('estado')) {
            $query->where('estado_incidencia', $request->estado);
        }
        if ($request->filled('prioridad')) {
            $query->where('prioridad_incidencia', $request->prioridad);
        }
        if ($request->filled('busqueda')) {
            // ilike = sin distinguir mayúsculas y minúsculas
            $query->where('nombre_incidencia', 'ilike', '%'.$request->busqueda.'%');
        }
        if ($request->filled('ciudad_id')) {
            $query->where('id_ciudad', $request->ciudad_id);
        }

        // El usuario "normal" solo ve sus propias incidencias
        $user = $request->user();
        if ($user->rol && $user->rol->nombre_rol === 'normal') {
            $query->where('id_usuario', $user->id);
        }

        return response()->json($query->paginate(10));
    }

    // Crear una incidencia
    public function crearIncidencia(Request $request)
    {
        $datos = $request->validate([
            'nombre_incidencia' => 'required|string|min:5|max:255',
            'descripcion_incidencia' => 'nullable|string',
            'direccion_incidencia' => 'nullable|string|max:500',
            'latitud_incidencia' => 'required|numeric',
            'longitud_incidencia' => 'required|numeric',
            'prioridad_incidencia' => 'required|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'required|exists:subtipos_incidencia,id_subtipo_incidencia',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Si vino una foto, la guardamos y su ruta queda en foto_incidencia
        if ($request->hasFile('foto')) {
            $datos['foto_incidencia'] = $request->file('foto')->store('incidencias', 'public');
        }
        unset($datos['foto']);

        // El dueño es el usuario autenticado (no lo manda el frontend)
        $datos['id_usuario'] = $request->user()->id;

        $incidencia = Incidencia::create($datos);

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']), 201);
    }
}
