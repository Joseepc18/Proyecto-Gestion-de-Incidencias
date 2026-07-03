<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SincronizarPermisosRequest;
use App\Models\Permiso;
use App\Models\Rol;

class PermisoController extends Controller
{
    // Matriz para la pantalla de permisos: cada rol con las claves que ya tiene + el catálogo completo.
    public function index()
    {
        $roles = Rol::with('permisos:id_permiso')->orderBy('id_rol')->get();
        $permisos = Permiso::orderBy('clave_permiso')->get(['id_permiso', 'clave_permiso', 'descripcion_permiso']);

        return response()->json([
            'roles' => $roles->map(fn ($rol) => [
                'id_rol' => $rol->id_rol,
                'nombre_rol' => $rol->nombre_rol,
                // El super_admin es el superset fijo: se muestra pero no se puede editar (anti-lockout).
                'editable' => $rol->nombre_rol !== 'super_admin',
                'permisos' => $rol->permisos->pluck('id_permiso'),
            ]),
            'permisos' => $permisos,
        ]);
    }

    // Reemplaza el set de permisos de un rol por el que envía el frontend (sync = agrega/quita el diff).
    public function sincronizar(SincronizarPermisosRequest $request, Rol $rol)
    {
        if ($rol->nombre_rol === 'super_admin') {
            return response()->json(['message' => 'El rol super_admin no se puede modificar'], 422);
        }

        $rol->permisos()->sync($request->validated()['permisos'] ?? []);

        return response()->json(['message' => 'Permisos actualizados']);
    }
}
