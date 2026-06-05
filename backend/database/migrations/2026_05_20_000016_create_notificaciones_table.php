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
        CREATE TABLE notificaciones(
            id_notificacion BIGSERIAL PRIMARY KEY,
            id_incidencia BIGINT NOT NULL,
            id_usuario BIGINT NOT NULL,
            mensaje_notificacion VARCHAR(255) NOT NULL,
            estado_lectura BOOLEAN DEFAULT FALSE,
            fecha_lectura TIMESTAMP NULL,
            tipo_notificacion VARCHAR(50) NOT NULL CHECK(tipo_notificacion IN ('ASIGNACION','CAMBIO_ESTADO','COMENTARIO','NUEVA_INCIDENCIA','EVIDENCIA')),
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE,
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
        DB::statement("DROP TABLE IF EXISTS notificaciones CASCADE;");
    }
};
