<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo de permisos base (clave => descripción). firstOrCreate = idempotente.
        $definiciones = [
            'incidencias.crear' => 'Reportar (registrar) una incidencia nueva',
            'incidencias.gestionar' => 'Gestionar incidencias: cambiar estado, asignar técnicos y prioridades',
            'incidencias.papelera' => 'Ver, restaurar y purgar la papelera de incidencias',
            'incidencias.eliminar' => 'Eliminar (enviar a la papelera) una incidencia',
            'dashboard.ver' => 'Ver el dashboard de métricas',
            'bitacora.ver' => 'Ver la bitácora de errores del sistema',
            'usuarios.administrar' => 'Administrar usuarios y sus roles',
            'catalogos.administrar' => 'Administrar tipos y subtipos de incidencia',
            'permisos.administrar' => 'Asignar permisos a los roles',
        ];

        $permisos = [];
        foreach ($definiciones as $clave => $descripcion) {
            $permisos[$clave] = Permiso::firstOrCreate(
                ['clave_permiso' => $clave],
                ['descripcion_permiso' => $descripcion]
            );
        }

        // Permisos base por rol. super_admin es view-only en incidencias (audita, no gestiona);
        // admin conserva todo lo operativo. Reportar es del ciudadano: si se le activa a otro rol,
        // ese rol podrá reportar pero no gestionar lo que él mismo reportó (conflicto de interés).
        $asignaciones = [
            'super_admin' => ['dashboard.ver', 'bitacora.ver', 'usuarios.administrar', 'catalogos.administrar', 'permisos.administrar'],
            'admin' => ['incidencias.gestionar', 'incidencias.papelera', 'incidencias.eliminar', 'dashboard.ver'],
            'tecnico' => [],
            'normal' => ['incidencias.crear'],
        ];

        foreach ($asignaciones as $nombreRol => $claves) {
            $rol = Rol::where('nombre_rol', $nombreRol)->first();
            if (! $rol) {
                continue;
            }

            $ids = array_map(fn ($clave) => $permisos[$clave]->id_permiso, $claves);
            $rol->permisos()->sync($ids);
        }
    }
}
