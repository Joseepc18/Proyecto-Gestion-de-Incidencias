<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    // Helpers de permisos

    private function esAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user->rol && $user->rol->nombre_rol === 'admin';
    }

    private function esTecnicoAsignado(Request $request, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()->where('id_usuario', $request->user()->id)->exists();
    }

    // Endpoints

    public function listadoIncidencias(Request $request)
    {
        $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionales
        if ($request->filled('estado')) {
            $query->where('estado_incidencia', $request->estado);
        }
        if ($request->filled('prioridad')) {
            $query->where('prioridad_incidencia', $request->prioridad);
        }
        if ($request->filled('busqueda')) {
            $query->where('nombre_incidencia', 'ilike', '%'.$request->busqueda.'%');
        }
        if ($request->filled('ciudad_id')) {
            $query->where('id_ciudad', $request->ciudad_id);
        }
        if ($request->filled('tipo_id')) {
            $query->whereHas('subtipo', fn ($q) => $q->where('id_tipo_incidencia', $request->tipo_id));
        }

        // Visibilidad por rol: normal ve solo las suyas; técnico solo donde está asignado
        $user = $request->user();

        if ($user->rol && $user->rol->nombre_rol === 'normal') {
            $query->where('id_usuario', $user->id);
        } elseif ($user->rol && $user->rol->nombre_rol === 'tecnico') {
            $query->whereHas('asignaciones', fn ($q) => $q->where('id_usuario', $user->id));
        }

        return response()->json($query->paginate(10));
    }

    public function crearIncidencia(Request $request)
    {
        // between de lat/long = rango geográfico de Ecuador
        $datos = $request->validate([
            'nombre_incidencia' => 'required|string|min:5|max:255',
            'descripcion_incidencia' => 'nullable|string',
            'direccion_incidencia' => 'nullable|string|max:500',
            'latitud_incidencia' => 'required|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'required|numeric|between:-82.0,-74.5',
            'prioridad_incidencia' => 'required|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'required|exists:subtipos_incidencia,id_subtipo_incidencia',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $datos['id_usuario'] = $request->user()->id;
        unset($datos['fotos']);

        try {
            $incidencia = Incidencia::create($datos);

            // Cada foto se guarda como una evidencia ligada a la incidencia
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $incidencia->evidencias()->create([
                        'url_evidencia' => $foto->store('incidencias', 'public'),
                        'id_usuario' => $request->user()->id,
                    ]);
                }
            }

            return response()->json(
                $incidencia->load(['usuario', 'subtipo.tipo', 'ciudad', 'evidencias']),
                201
            );
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'IncidenciaController@crearIncidencia: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al crear la incidencia'], 500);
        }
    }

    public function verIncidencia(Request $request, Incidencia $incidencia)
    {
        // El "normal" solo puede ver las suyas
        $user = $request->user();
        if ($user->rol && $user->rol->nombre_rol === 'normal' && $incidencia->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad', 'evidencias']));
    }

    public function actualizarIncidencia(Request $request, Incidencia $incidencia)
    {
        // Pueden editar: admin, autor o técnico asignado
        $esAdmin = $this->esAdmin($request);
        $esAutor = $incidencia->id_usuario === $request->user()->id;
        $esTecnico = $this->esTecnicoAsignado($request, $incidencia);

        if (! $esAdmin && ! $esAutor && ! $esTecnico) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // El autor (ciudadano) solo puede editar mientras está PENDIENTE.
        // Cuando el admin la pone EN_PROCESO o RESUELTO, ya no puede modificarla.
        if ($esAutor && ! $esAdmin && ! $esTecnico && $incidencia->estado_incidencia !== 'PENDIENTE') {
            return response()->json([
                'message' => 'No puedes editar esta incidencia porque ya está en proceso. Usa los comentarios para comunicarte con el equipo.',
            ], 403);
        }

        $datos = $request->validate([
            'nombre_incidencia' => 'sometimes|string|min:5|max:255',
            'descripcion_incidencia' => 'sometimes|nullable|string',
            'direccion_incidencia' => 'sometimes|nullable|string|max:500',
            'latitud_incidencia' => 'sometimes|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'sometimes|numeric|between:-82.0,-74.5',
            'prioridad_incidencia' => 'sometimes|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'sometimes|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'sometimes|exists:subtipos_incidencia,id_subtipo_incidencia',
        ]);

        $incidencia->update($datos);

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    public function eliminarIncidencia(Request $request, Incidencia $incidencia)
    {
        // Eliminar: solo admin o autor
        if (! $this->esAdmin($request) && $incidencia->id_usuario !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            // La cascada borra las filas de evidencias, pero los archivos hay que borrarlos a mano
            foreach ($incidencia->evidencias as $evidencia) {
                Storage::disk('public')->delete($evidencia->url_evidencia);
            }

            $incidencia->delete();

            return response()->json(['message' => 'Incidencia eliminada']);
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'IncidenciaController@eliminarIncidencia: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al eliminar la incidencia'], 500);
        }
    }

    public function historialIncidencia(Request $request, Incidencia $incidencia)
    {
        // El usuario normal solo puede ver sus propias incidencias
        $user = $request->user();
        if ($user->rol && $user->rol->nombre_rol === 'normal' && $incidencia->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json(
            $incidencia->historialEstados()->with('usuario')->orderBy('created_at', 'desc')->get()
        );
    }

    // Cambiar el estado (flujo de trabajo): admin o técnico asignado.
    public function cambiarEstado(Request $request, Incidencia $incidencia)
    {
        $request->validate([
            'estado_incidencia' => 'required|in:PENDIENTE,EN_PROCESO,RESUELTO',
        ]);

        $nuevo = $request->estado_incidencia;
        $actual = $incidencia->estado_incidencia;

        if ($this->esAdmin($request)) {
            // Admin: cualquier cambio, sin restricción
        } elseif ($this->esTecnicoAsignado($request, $incidencia)) {
            // Técnico: solo la transición "siguiente" permitida (avanzar)
            $siguientePermitido = [
                'PENDIENTE' => 'EN_PROCESO',
                'EN_PROCESO' => 'RESUELTO',
            ];
            if (($siguientePermitido[$actual] ?? null) !== $nuevo) {
                return response()->json(['message' => 'Transición de estado no permitida'], 422);
            }
        } else {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $incidencia->update(['estado_incidencia' => $nuevo]);

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }
}
