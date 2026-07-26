<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SincronizarPermisosRequest;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Support\Collection;

class PermisoController extends Controller
{
    // Permisos que ni un super_admin puede darle a normal/tecnico: son roles operativos por asignación
    // (RolAsignacion) o ciudadanos, nunca gestores del sistema.
    private const PERMISOS_PRIVILEGIADOS = ['incidencias.gestionar', 'usuarios.administrar', 'permisos.administrar'];

    // Gobierno del sistema: sin estos dos, el super_admin no puede devolverse permisos ni arreglar roles,
    // y no queda forma de recuperarse desde la aplicación. Van marcados y bloqueados en su fila.
    private const PERMISOS_FIJOS_SUPER_ADMIN = ['permisos.administrar', 'usuarios.administrar'];

    // Matriz para la pantalla de permisos: cada rol con las claves que ya tiene + el catálogo completo.
    public function index()
    {
        $roles = Rol::with('permisos:id_permiso')->orderBy('id_rol')->get();
        $permisos = Permiso::orderBy('clave_permiso')->get(['id_permiso', 'clave_permiso', 'descripcion_permiso']);
        $idsFijos = $this->idsPermisosFijos();

        return response()->json([
            'roles' => $roles->map(fn ($rol) => [
                'id_rol' => $rol->id_rol,
                'nombre_rol' => $rol->nombre_rol,
                // Todos los roles son editables; las redes de seguridad son el anti-lockout y los permisos fijos de sincronizar().
                'editable' => true,
                'permisos' => $rol->permisos->pluck('id_permiso'),
                // Casillas que el frontend pinta marcadas y bloqueadas; el rechazo real lo hace sincronizar().
                'permisos_fijos' => $rol->nombre_rol === Rol::SUPER_ADMIN ? $idsFijos->values() : [],
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

        if ($this->quitaPermisoFijado($rol, $nuevosIds)) {
            return response()->json(['message' => 'El Administrador del Sistema no puede quedarse sin los permisos de gobierno (usuarios y permisos)'], 422);
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

    // Guard de gobierno: el set nuevo del super_admin tiene que seguir incluyendo sus permisos fijos.
    // El backend es la validación real; la casilla bloqueada del panel es solo la ayuda visual.
    private function quitaPermisoFijado(Rol $rol, array $nuevosIds): bool
    {
        if ($rol->nombre_rol !== Rol::SUPER_ADMIN) {
            return false;
        }

        return $this->idsPermisosFijos()->diff($nuevosIds)->isNotEmpty();
    }

    // Ids de los permisos de gobierno; se resuelven por clave porque los ids dependen del orden del seeder.
    private function idsPermisosFijos(): Collection
    {
        return Permiso::whereIn('clave_permiso', self::PERMISOS_FIJOS_SUPER_ADMIN)->pluck('id_permiso');
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
