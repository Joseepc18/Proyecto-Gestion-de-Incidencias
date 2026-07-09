<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
        CREATE TABLE permisos(
            id_permiso BIGSERIAL PRIMARY KEY,
            clave_permiso VARCHAR(255) NOT NULL UNIQUE,
            descripcion_permiso TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
        DROP TABLE IF EXISTS permisos CASCADE;
        ');
    }
};
