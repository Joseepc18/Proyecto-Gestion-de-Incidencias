<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    // Ver una incidencia con sus datos completos
    public function verIncidencia(Request $request, Incidencia $incidencia)
    {
        // El usuario "normal" solo puede ver las suyas.
        $user = $request->user();
        if ($user->rol && $user->rol->nombre_rol === 'normal' && $incidencia->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    // Actualizar una incidencia (admin, autor o técnico asignado).
    public function actualizarIncidencia(Request $request, Incidencia $incidencia)
    {
        $user = $request->user();

        // Permisos: admin, el autor, o un técnico asignado.
        $esAdmin = $user->rol && $user->rol->nombre_rol === 'admin';
        $esAutor = $incidencia->id_usuario === $user->id;
        $esTecnicoAsignado = $incidencia->asignaciones()->where('id_usuario', $user->id)->exists();

        if (! $esAdmin && ! $esAutor && ! $esTecnicoAsignado) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 'sometimes' = validar solo si el campo viene.
        $datos = $request->validate([
            'nombre_incidencia' => 'sometimes|string|min:5|max:255',
            'descripcion_incidencia' => 'sometimes|nullable|string',
            'direccion_incidencia' => 'sometimes|nullable|string|max:500',
            'latitud_incidencia' => 'sometimes|numeric',
            'longitud_incidencia' => 'sometimes|numeric',
            'estado_incidencia' => 'sometimes|in:PENDIENTE,EN_PROCESO,RESUELTO',
            'prioridad_incidencia' => 'sometimes|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'sometimes|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'sometimes|exists:subtipos_incidencia,id_subtipo_incidencia',
        ]);

        $incidencia->update($datos);

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    // Eliminar una incidencia (solo admin o autor).
    public function eliminarIncidencia(Request $request, Incidencia $incidencia)
    {
        $user = $request->user();
        $esAdmin = $user->rol && $user->rol->nombre_rol === 'admin';
        $esAutor = $incidencia->id_usuario === $user->id;

        if (! $esAdmin && ! $esAutor) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Borrar los archivos de las evidencias del disco antes de eliminar.
        foreach ($incidencia->evidencias as $evidencia) {
            Storage::disk('public')->delete($evidencia->url_evidencia);
        }

        $incidencia->delete();

        return response()->json(['message' => 'Incidencia eliminada']);
    }

    // Historial de cambios de estado (más reciente primero).
    public function historialIncidencia(Incidencia $incidencia)
    {
        return response()->json(
            $incidencia->historialEstados()->with('usuario')->orderBy('created_at', 'desc')->get()
        );
    }
}
