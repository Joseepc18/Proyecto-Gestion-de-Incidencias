<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
        CREATE TABLE asignaciones_incidencia(
            id_asignacion BIGSERIAL PRIMARY KEY,
            id_incidencia BIGINT NOT NULL,
            id_usuario BIGINT NOT NULL,
            rol_asignado VARCHAR(50) NOT NULL CHECK(rol_asignado IN ('RESPONSABLE','APOYO')),
            UNIQUE(id_incidencia, id_usuario),
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ");

        DB::statement("
            CREATE UNIQUE INDEX idx_un_responsable_por_incidencia 
            ON asignaciones_incidencia(id_incidencia) 
            WHERE rol_asignado = 'RESPONSABLE';
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS asignaciones_incidencia CASCADE;");
    }
};
