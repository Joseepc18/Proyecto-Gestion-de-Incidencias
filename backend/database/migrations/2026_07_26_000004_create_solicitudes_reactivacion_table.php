<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Vía formal para que una cuenta suspendida pida que la reactiven, ahora que su correo queda bloqueado.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
        CREATE TABLE solicitudes_reactivacion(
            id_solicitud BIGSERIAL PRIMARY KEY,
            id_usuario BIGINT NOT NULL,
            motivo_solicitud VARCHAR(500) NOT NULL,
            estado_solicitud VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE' CHECK(estado_solicitud IN ('PENDIENTE','APROBADA','RECHAZADA')),
            id_admin_resuelve BIGINT NULL,
            fecha_resolucion TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (id_admin_resuelve) REFERENCES users(id) ON DELETE SET NULL
        );
        ");

        // Una sola pendiente por usuario: el anti-spam lo hace cumplir Postgres, no el controller.
        DB::statement("
        CREATE UNIQUE INDEX solicitudes_reactivacion_pendiente_unica
        ON solicitudes_reactivacion (id_usuario) WHERE estado_solicitud = 'PENDIENTE';
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS solicitudes_reactivacion CASCADE;');
    }
};
