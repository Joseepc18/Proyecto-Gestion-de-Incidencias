<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vista v_metricas_por_ubicacion: incidencias por ciudad y desglose por estado (solo ciudades con incidencias; JOIN, no LEFT JOIN).
        DB::unprepared("
            CREATE OR REPLACE VIEW v_metricas_por_ubicacion AS
            SELECT
                -- Ubicación
                c.id_ciudad,
                c.nombre_ciudad,
                p.nombre_provincia,

                -- Conteos por estado
                COUNT(i.id_incidencia)                                                   AS total,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'PENDIENTE')   AS total_pendientes,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'EN_PROCESO')  AS total_en_proceso,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'RESUELTO')    AS total_resueltas

            FROM incidencias i
            JOIN ciudades   c ON i.id_ciudad    = c.id_ciudad
            JOIN provincias p ON c.id_provincia = p.id_provincia
            GROUP BY c.id_ciudad, c.nombre_ciudad, p.nombre_provincia
            ORDER BY total DESC;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_metricas_por_ubicacion;');
    }
};
