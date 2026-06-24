<?php

use Illuminate\Database\Migrations\Migration;

// H-07: la columna foto_incidencia quedó muerta (las fotos viven en la tabla
// evidencias). La vista v_incidencias_completas todavía la arrastraba, así que
// hay que recrear la vista sin esa columna ANTES de poder dropearla.
return new class extends Migration
{
    public function up(): void
    {
        // La vista depende de la columna: se borra primero para liberarla.
        DB::unprepared('DROP VIEW IF EXISTS v_incidencias_completas;');

        DB::statement('ALTER TABLE incidencias DROP COLUMN IF EXISTS foto_incidencia;');

        // Misma vista, ya sin foto_incidencia.
        DB::unprepared('
            CREATE VIEW v_incidencias_completas AS
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
            JOIN users               u ON i.id_usuario             = u.id
            JOIN subtipos_incidencia s ON i.id_subtipo_incidencia  = s.id_subtipo_incidencia
            JOIN tipos_incidencia    t ON s.id_tipo_incidencia      = t.id_tipo_incidencia
            JOIN ciudades            c ON i.id_ciudad               = c.id_ciudad
            JOIN provincias          p ON c.id_provincia            = p.id_provincia;
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_incidencias_completas;');

        DB::statement('ALTER TABLE incidencias ADD COLUMN foto_incidencia VARCHAR(500);');

        // Vista original (con foto_incidencia de vuelta).
        DB::unprepared('
            CREATE VIEW v_incidencias_completas AS
            SELECT
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
            JOIN users               u ON i.id_usuario             = u.id
            JOIN subtipos_incidencia s ON i.id_subtipo_incidencia  = s.id_subtipo_incidencia
            JOIN tipos_incidencia    t ON s.id_tipo_incidencia      = t.id_tipo_incidencia
            JOIN ciudades            c ON i.id_ciudad               = c.id_ciudad
            JOIN provincias          p ON c.id_provincia            = p.id_provincia;
        ');
    }
};
