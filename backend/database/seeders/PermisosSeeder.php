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
            'incidencias.gestionar' => 'Gestionar incidencias: asignar técnicos, dashboard y prioridades',
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

        // Permisos base por rol. super_admin es el superset; admin conserva lo operativo.
        $asignaciones = [
            'super_admin' => ['incidencias.gestionar', 'usuarios.administrar', 'catalogos.administrar', 'permisos.administrar'],
            'admin' => ['incidencias.gestionar'],
            'tecnico' => [],
            'normal' => [],
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
