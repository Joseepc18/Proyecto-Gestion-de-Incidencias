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
        CREATE TABLE historial_estados(
            id_historial BIGSERIAL PRIMARY KEY,
            id_incidencia BIGINT NOT NULL,
            id_usuario BIGINT NULL,
            estado_anterior VARCHAR(50) NULL CHECK(estado_anterior IN ('PENDIENTE','EN_PROCESO','RESUELTO')),
            estado_nuevo VARCHAR(50) NOT NULL CHECK(estado_nuevo IN ('PENDIENTE','EN_PROCESO','RESUELTO')),
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS historial_estados CASCADE;");
    }
};
