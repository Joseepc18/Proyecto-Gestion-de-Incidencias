<?php

use Illuminate\Database\Migrations\Migration;

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
            -- NULL solo para INCIDENCIA_ELIMINADA: la incidencia ya no existe (se borra físico),
            -- así que esta notificación no puede depender de esa fila para sobrevivir al DELETE CASCADE.
            id_incidencia BIGINT NULL,
            id_usuario BIGINT NOT NULL,
            -- 600 (no 255): el mensaje de INCIDENCIA_ELIMINADA incluye el nombre + el motivo escrito por el admin.
            mensaje_notificacion VARCHAR(600) NOT NULL,
            estado_lectura BOOLEAN DEFAULT FALSE,
            fecha_lectura TIMESTAMP NULL,
            tipo_notificacion VARCHAR(50) NOT NULL CHECK(tipo_notificacion IN ('ASIGNACION','CAMBIO_ESTADO','COMENTARIO','NUEVA_INCIDENCIA','EVIDENCIA','INCIDENCIA_ELIMINADA','SOLICITUD_REAPERTURA')),
            -- Comentarios agrupados en una misma notificación de COMENTARIO sin leer.
            contador INT NOT NULL DEFAULT 1,
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ");

        // Solo una notificación de COMENTARIO sin leer por (usuario, incidencia): ancla del UPSERT de fn_notificar_nuevo_comentario.
        DB::statement("
            CREATE UNIQUE INDEX uq_notif_comentario_pendiente
                ON notificaciones (id_usuario, id_incidencia)
                WHERE tipo_notificacion = 'COMENTARIO' AND estado_lectura = false;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS notificaciones CASCADE;');
    }
};
