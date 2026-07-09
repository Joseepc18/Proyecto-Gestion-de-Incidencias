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
        CREATE TABLE subtipos_incidencia(
            id_subtipo_incidencia BIGSERIAL PRIMARY KEY,
            nombre_subtipo_incidencia VARCHAR(255) NOT NULL,
            id_tipo_incidencia BIGINT NOT NULL,
            FOREIGN KEY (id_tipo_incidencia) REFERENCES tipos_incidencia(id_tipo_incidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
            descripcion_subtipo_incidencia TEXT,
            UNIQUE(nombre_subtipo_incidencia, id_tipo_incidencia),
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
        DROP TABLE IF EXISTS subtipos_incidencia CASCADE;
        ');
    }
};
