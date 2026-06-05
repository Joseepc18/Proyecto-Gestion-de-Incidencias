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
        // VISTA 1: v_incidencias_completas
        // Une incidencias con usuario, subtipo, tipo, ciudad y provincia en una
        // sola consulta. Evita repetir los mismos JOINs en cada controlador.
        DB::unprepared("
            CREATE OR REPLACE VIEW v_incidencias_completas AS
            SELECT
                -- Datos de la incidencia
                i.id_incidencia,
                i.nombre_incidencia,
                i.descripcion_incidencia,
                i.direccion_incidencia,
                i.latitud_incidencia,
                i.longitud_incidencia,
                i.prioridad_incidencia,
                i.estado_incidencia,
                i.foto_incidencia,
                i.fecha_resolucion,
                i.created_at,

                -- Quién la reportó
                u.id            AS id_usuario,
                u.name          AS nombre_usuario,
                u.email         AS email_usuario,

                -- Subtipo y tipo de incidencia
                s.id_subtipo_incidencia,
                s.nombre_subtipo_incidencia,
                t.id_tipo_incidencia,
                t.nombre_tipo_incidencia,

                -- Ubicación geográfica
                c.id_ciudad,
                c.nombre_ciudad,
                p.id_provincia,
                p.nombre_provincia

            FROM incidencias i
            JOIN users              u ON i.id_usuario             = u.id
            JOIN subtipos_incidencia s ON i.id_subtipo_incidencia  = s.id_subtipo_incidencia
            JOIN tipos_incidencia    t ON s.id_tipo_incidencia     = t.id_tipo_incidencia
            JOIN ciudades            c ON i.id_ciudad              = c.id_ciudad
            JOIN provincias          p ON c.id_provincia           = p.id_provincia;
        ");

        // VISTA 2: v_metricas_por_tipo
        // Estadísticas agrupadas por tipo para el dashboard: totales por estado
        // y promedio de días de resolución de las incidencias ya resueltas.
        DB::unprepared("
            CREATE OR REPLACE VIEW v_metricas_por_tipo AS
            SELECT
                -- El tipo de incidencia
                t.id_tipo_incidencia,
                t.nombre_tipo_incidencia,

                -- Conteos por estado
                COUNT(i.id_incidencia)                                          AS total,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'PENDIENTE')   AS total_pendientes,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'EN_PROCESO')  AS total_en_proceso,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'RESUELTO')    AS total_resueltas,

                -- Promedio de días que tarda en resolverse (solo las que ya se resolvieron)
                ROUND(
                    AVG(
                        EXTRACT(EPOCH FROM (i.fecha_resolucion - i.created_at)) / 86400
                    ) FILTER (WHERE i.fecha_resolucion IS NOT NULL),
                2) AS promedio_dias_resolucion

            FROM tipos_incidencia t
            LEFT JOIN subtipos_incidencia s ON t.id_tipo_incidencia   = s.id_tipo_incidencia
            LEFT JOIN incidencias         i ON s.id_subtipo_incidencia = i.id_subtipo_incidencia
            GROUP BY t.id_tipo_incidencia, t.nombre_tipo_incidencia
            ORDER BY total DESC;
        ");

        // FUNCIÓN: calcular_tiempo_resolucion
        // Retorna los días exactos que tardó en resolverse una incidencia específica.
        // Si aún no está resuelta, retorna NULL.
        DB::unprepared("
            CREATE OR REPLACE FUNCTION calcular_tiempo_resolucion(p_id_incidencia BIGINT)
            RETURNS NUMERIC AS \$\$
            DECLARE
                v_dias NUMERIC;
            BEGIN
                -- Calcula la diferencia entre resolución y creación en días
                SELECT ROUND(
                    EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400
                , 2)
                INTO v_dias
                FROM incidencias
                WHERE id_incidencia = p_id_incidencia
                AND fecha_resolucion IS NOT NULL;

                -- Si no está resuelta aún, retorna NULL
                RETURN v_dias;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // ÍNDICES DE RENDIMIENTO
        // Aceleran las consultas más frecuentes del sistema evitando que PostgreSQL
        // recorra toda la tabla fila por fila para encontrar los registros.
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_incidencias_estado ON incidencias(estado_incidencia);");
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_incidencias_usuario ON incidencias(id_usuario);");
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_comentarios_incidencia ON comentarios(id_incidencia);");
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_historial_incidencia ON historial_estados(id_incidencia);");
        DB::unprepared("CREATE INDEX IF NOT EXISTS idx_notificaciones_usuario ON notificaciones(id_usuario);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP VIEW IF EXISTS v_incidencias_completas;");
        DB::unprepared("DROP VIEW IF EXISTS v_metricas_por_tipo;");
        DB::unprepared("DROP FUNCTION IF EXISTS calcular_tiempo_resolucion(BIGINT);");
        DB::unprepared("DROP INDEX IF EXISTS idx_incidencias_estado;");
        DB::unprepared("DROP INDEX IF EXISTS idx_incidencias_usuario;");
        DB::unprepared("DROP INDEX IF EXISTS idx_comentarios_incidencia;");
        DB::unprepared("DROP INDEX IF EXISTS idx_historial_incidencia;");
        DB::unprepared("DROP INDEX IF EXISTS idx_notificaciones_usuario;");
    }
};