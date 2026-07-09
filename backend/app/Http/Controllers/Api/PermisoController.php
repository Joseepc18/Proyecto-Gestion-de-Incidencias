<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SincronizarPermisosRequest;
use App\Models\Permiso;
use App\Models\Rol;

class PermisoController extends Controller
{
    // Permisos que ni un super_admin puede darle a normal/tecnico: son roles operativos por asignación
    // (RolAsignacion) o ciudadanos, nunca gestores del sistema.
    private const PERMISOS_PRIVILEGIADOS = ['incidencias.gestionar', 'usuarios.administrar', 'permisos.administrar'];

    // Matriz para la pantalla de permisos: cada rol con las claves que ya tiene + el catálogo completo.
    public function index()
    {
        $roles = Rol::with('permisos:id_permiso')->orderBy('id_rol')->get();
        $permisos = Permiso::orderBy('clave_permiso')->get(['id_permiso', 'clave_permiso', 'descripcion_permiso']);

        return response()->json([
            'roles' => $roles->map(fn ($rol) => [
                'id_rol' => $rol->id_rol,
                'nombre_rol' => $rol->nombre_rol,
                // Todos los roles son editables; la única red de seguridad es el anti-lockout de sincronizar().
                'editable' => true,
                'permisos' => $rol->permisos->pluck('id_permiso'),
            ]),
            'permisos' => $permisos,
        ]);
    }

    // Reemplaza el set de permisos de un rol por el que envía el frontend (sync = agrega/quita el diff).
    public function sincronizar(SincronizarPermisosRequest $request, Rol $rol)
    {
        $nuevosIds = $request->validated()['permisos'] ?? [];

        if ($this->dejaSinAdministradores($rol, $nuevosIds)) {
            return response()->json(['message' => 'Debe quedar al menos un rol con el permiso permisos.administrar'], 422);
        }

        if ($this->otorgaPermisoPrivilegiadoARolBajo($rol, $nuevosIds)) {
            return response()->json(['message' => 'Ese permiso no se puede asignar a normal/tecnico'], 422);
        }

        $rol->permisos()->sync($nuevosIds);

        return response()->json(['message' => 'Permisos actualizados']);
    }

    // Guard: normal/tecnico nunca deben recibir un permiso de la lista privilegiada, aunque lo pida un super_admin.
    private function otorgaPermisoPrivilegiadoARolBajo(Rol $rol, array $nuevosIds): bool
    {
        if (! in_array($rol->nombre_rol, [Rol::NORMAL, Rol::TECNICO], true)) {
            return false;
        }

        $idsPrivilegiados = Permiso::whereIn('clave_permiso', self::PERMISOS_PRIVILEGIADOS)->pluck('id_permiso');

        return $idsPrivilegiados->intersect($nuevosIds)->isNotEmpty();
    }

    // Anti-lockout: si este cambio deja a $rol sin permisos.administrar, tiene que quedar OTRO rol que sí lo tenga.
    private function dejaSinAdministradores(Rol $rol, array $nuevosIds): bool
    {
        $idAdministrar = Permiso::where('clave_permiso', 'permisos.administrar')->value('id_permiso');
        if (! $idAdministrar || in_array($idAdministrar, $nuevosIds, true)) {
            return false;
        }

        $otroRolLoTiene = Rol::where('id_rol', '!=', $rol->id_rol)
            ->whereHas('permisos', fn ($q) => $q->where('clave_permiso', 'permisos.administrar'))
            ->exists();

        return ! $otroRolLoTiene;
    }
}
