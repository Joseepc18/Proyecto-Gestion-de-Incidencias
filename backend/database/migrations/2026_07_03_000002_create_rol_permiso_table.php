<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pivote N:N entre roles y permisos; PK compuesta evita duplicados y el CASCADE limpia al borrar un rol o permiso.
        DB::statement('
        CREATE TABLE rol_permiso(
            id_rol BIGINT NOT NULL,
            id_permiso BIGINT NOT NULL,
            PRIMARY KEY (id_rol, id_permiso),
            CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
            CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
        );
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
        DROP TABLE IF EXISTS rol_permiso CASCADE;
        ');
    }
};
