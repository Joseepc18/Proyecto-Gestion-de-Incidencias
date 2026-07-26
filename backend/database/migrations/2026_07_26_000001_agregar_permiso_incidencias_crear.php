<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Incremental, no consolidada en create_permisos_table: la BD de producción no es descartable y esa
// migración ya corrió allá, así que editarla no aplicaría nada. Idempotente (insertOrIgnore) para
// poder re-correrla sin duplicar filas.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permisos')->insertOrIgnore([
            'clave_permiso' => 'incidencias.crear',
            'descripcion_permiso' => 'Reportar (registrar) una incidencia nueva',
        ]);

        $idPermiso = DB::table('permisos')->where('clave_permiso', 'incidencias.crear')->value('id_permiso');
        $idRol = DB::table('roles')->where('nombre_rol', 'normal')->value('id_rol');

        // Solo el ciudadano nace con el permiso: reportar es su función. Técnico, Supervisor y
        // Administrador del Sistema quedan sin él y se activan desde el panel de permisos si hace falta.
        if ($idPermiso && $idRol) {
            DB::table('rol_permiso')->insertOrIgnore([
                'id_rol' => $idRol,
                'id_permiso' => $idPermiso,
            ]);
        }
    }

    public function down(): void
    {
        // Las filas del pivote caen solas por el ON DELETE CASCADE de rol_permiso.
        DB::table('permisos')->where('clave_permiso', 'incidencias.crear')->delete();
    }
};
