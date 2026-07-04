<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vista v_incidencias_completas: une 6 tablas; DEMOSTRATIVA (rúbrica "BD avanzada"), la app usa Eloquent + eager loading.
        DB::unprepared('
            CREATE OR REPLACE VIEW v_incidencias_completas AS
            SELECT
                i.id_incidencia,
                i.nombre_incidencia,
                i.descripcion_incidencia,
                i.direccion_incidencia,
                i.latitud_incidencia,
                i.longitud_incidencia,
                i.prioridad_incidencia,
                i.estado_incidencia,
                i.fecha_resolucion,
                i.created_at,

                u.id            AS id_usuario,
                u.name          AS nombre_usuario,
                u.email         AS email_usuario,

                s.id_subtipo_incidencia,
                s.nombre_subtipo_incidencia,
                t.id_tipo_incidencia,
                t.nombre_tipo_incidencia,

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
        ');

        // Vista v_metricas_por_tipo: totales por estado y promedio de días de resolución, agrupados por tipo (dashboard).
        DB::unprepared("
            CREATE OR REPLACE VIEW v_metricas_por_tipo AS
            SELECT
                t.id_tipo_incidencia,
                t.nombre_tipo_incidencia,

                COUNT(i.id_incidencia)                                          AS total,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'PENDIENTE')   AS total_pendientes,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'EN_PROCESO')  AS total_en_proceso,
                COUNT(i.id_incidencia) FILTER (WHERE i.estado_incidencia = 'RESUELTO')    AS total_resueltas,

                -- Promedio de días de resolución (solo las incidencias ya resueltas)
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

        // Vista v_metricas_por_ubicacion: incidencias por ciudad y desglose por estado (solo ciudades con incidencias).
        DB::unprepared("
            CREATE OR REPLACE VIEW v_metricas_por_ubicacion AS
            SELECT
                c.id_ciudad,
                c.nombre_ciudad,
                p.nombre_provincia,

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

        // Función calcular_tiempo_resolucion: días que tardó en resolverse (NULL si no); DEMOSTRATIVA, el dashboard usa agregados.
        DB::unprepared('
            CREATE OR REPLACE FUNCTION calcular_tiempo_resolucion(p_id_incidencia BIGINT)
            RETURNS NUMERIC AS $$
            DECLARE
                v_dias NUMERIC;
            BEGIN
                SELECT ROUND(
                    EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400
                , 2)
                INTO v_dias
                FROM incidencias
                WHERE id_incidencia = p_id_incidencia
                AND fecha_resolucion IS NOT NULL;

                -- Queda NULL si la incidencia todavía no se resuelve
                RETURN v_dias;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Índices de rendimiento: aceleran las consultas más frecuentes evitando recorrer toda la tabla.
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_estado ON incidencias(estado_incidencia);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_usuario ON incidencias(id_usuario);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_comentarios_incidencia ON comentarios(id_incidencia);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_historial_incidencia ON historial_estados(id_incidencia);');
        // Acelera el conteo de evidencias por incidencia (trigger fn_limite_evidencias y EvidenciaController@subir).
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_evidencias_incidencia ON evidencias(id_incidencia);');
        // Postgres no indexa las FK automáticamente: id_ciudad e id_subtipo_incidencia se filtraban/joineaban sin índice.
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_ciudad ON incidencias(id_ciudad);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_subtipo ON incidencias(id_subtipo_incidencia);');
        // El UNIQUE(id_incidencia, id_usuario) no sirve para filtrar solo por id_usuario (esResponsableDe, DashboardController).
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_asignaciones_usuario ON asignaciones_incidencia(id_usuario);');
        // Acelera "reclamar" (whereNull/where id_admin_atiende) y la columna "Atendido por" del listado.
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_admin_atiende ON incidencias(id_admin_atiende);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_incidencias_completas;');
        DB::unprepared('DROP VIEW IF EXISTS v_metricas_por_tipo;');
        DB::unprepared('DROP VIEW IF EXISTS v_metricas_por_ubicacion;');
        DB::unprepared('DROP FUNCTION IF EXISTS calcular_tiempo_resolucion(BIGINT);');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_estado;');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_usuario;');
        DB::unprepared('DROP INDEX IF EXISTS idx_comentarios_incidencia;');
        DB::unprepared('DROP INDEX IF EXISTS idx_historial_incidencia;');
        DB::unprepared('DROP INDEX IF EXISTS idx_evidencias_incidencia;');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_ciudad;');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_subtipo;');
        DB::unprepared('DROP INDEX IF EXISTS idx_asignaciones_usuario;');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_admin_atiende;');
    }
};
