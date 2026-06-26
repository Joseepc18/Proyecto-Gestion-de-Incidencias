<?php

use Illuminate\Database\Migrations\Migration;

// Notificaciones internas (campana). Regla: nunca se notifica al actor; el del cambio de estado se lee de app.actor_id (la publica la app antes del UPDATE).
// Las evidencias (#8/#9) NO van aquí sino en EvidenciaController@subir (un trigger no distingue las fotos iniciales de las añadidas después).
return new class extends Migration
{
    public function up(): void
    {
        // #1 — Nueva incidencia: avisa a todos los administradores.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_nueva_incidencia()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                SELECT u.id, NEW.id_incidencia, 'NUEVA_INCIDENCIA',
                       'Nueva incidencia reportada: ' || NEW.nombre_incidencia
                FROM users u
                JOIN roles r ON u.id_rol = r.id_rol
                WHERE r.nombre_rol = 'admin'
                  AND u.id <> NEW.id_usuario;   -- nunca al propio creador
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_nueva_incidencia ON incidencias;');
        DB::unprepared('
        CREATE TRIGGER tr_notificar_nueva_incidencia
            AFTER INSERT ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_notificar_nueva_incidencia();
        ');

        // #2/#3/#4 — Asignación: avisa al técnico asignado (responsable o apoyo)
        // y, si es RESPONSABLE, también al reportador ("ya está en gestión").
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_asignacion()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_reportador BIGINT;
                v_nombre     VARCHAR;
            BEGIN
                SELECT id_usuario, nombre_incidencia
                INTO v_reportador, v_nombre
                FROM incidencias WHERE id_incidencia = NEW.id_incidencia;

                -- Al técnico asignado.
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                VALUES (NEW.id_usuario, NEW.id_incidencia, 'ASIGNACION',
                        'Te asignaron a una incidencia (' || NEW.rol_asignado || '): ' || v_nombre);

                -- Al reportador, solo cuando se nombra un RESPONSABLE.
                IF NEW.rol_asignado = 'RESPONSABLE' AND v_reportador <> NEW.id_usuario THEN
                    INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                    VALUES (v_reportador, NEW.id_incidencia, 'ASIGNACION',
                            'Tu incidencia ya tiene un responsable asignado: ' || v_nombre);
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_asignacion ON asignaciones_incidencia;');
        DB::unprepared('
        CREATE TRIGGER tr_notificar_asignacion
            AFTER INSERT ON asignaciones_incidencia
            FOR EACH ROW
            EXECUTE FUNCTION fn_notificar_asignacion();
        ');

        // #6 — Cambio de estado (menos RESUELTO, que ya lo cubre resolver_incidencia):
        // avisa al reportador y a los técnicos asignados, salvo a quien ejecuta.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_cambio_estado()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_actor BIGINT;
            BEGIN
                IF NEW.estado_incidencia <> OLD.estado_incidencia
                   AND NEW.estado_incidencia <> 'RESUELTO' THEN
                    v_actor := NULLIF(current_setting('app.actor_id', true), '')::BIGINT;

                    -- Al reportador (IS DISTINCT FROM trata bien el actor NULL).
                    IF NEW.id_usuario IS DISTINCT FROM v_actor THEN
                        INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                        VALUES (NEW.id_usuario, NEW.id_incidencia, 'CAMBIO_ESTADO',
                                'Tu incidencia cambió a ' || NEW.estado_incidencia || ': ' || NEW.nombre_incidencia);
                    END IF;

                    -- A los técnicos asignados, menos el actor.
                    INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                    SELECT a.id_usuario, NEW.id_incidencia, 'CAMBIO_ESTADO',
                           'La incidencia cambió a ' || NEW.estado_incidencia || ': ' || NEW.nombre_incidencia
                    FROM asignaciones_incidencia a
                    WHERE a.id_incidencia = NEW.id_incidencia
                      AND a.id_usuario IS DISTINCT FROM v_actor;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_cambio_estado ON incidencias;');
        DB::unprepared('
        CREATE TRIGGER tr_notificar_cambio_estado
            AFTER UPDATE ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_notificar_cambio_estado();
        ');

        // #7 — Comentario (amplía el trigger existente): avisa a todos los del
        // chat (reportador + admins + técnico responsable), menos al autor.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_nuevo_comentario()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_reportador BIGINT;
                v_nombre     VARCHAR;
            BEGIN
                SELECT id_usuario, nombre_incidencia
                INTO v_reportador, v_nombre
                FROM incidencias WHERE id_incidencia = NEW.id_incidencia;

                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                SELECT DISTINCT d.id_usuario, NEW.id_incidencia, 'COMENTARIO',
                       'Nuevo comentario en la incidencia: ' || v_nombre
                FROM (
                    SELECT v_reportador AS id_usuario
                    UNION
                    SELECT u.id FROM users u
                        JOIN roles r ON u.id_rol = r.id_rol
                        WHERE r.nombre_rol = 'admin'
                    UNION
                    SELECT a.id_usuario FROM asignaciones_incidencia a
                        WHERE a.id_incidencia = NEW.id_incidencia
                          AND a.rol_asignado = 'RESPONSABLE'
                ) d
                WHERE d.id_usuario IS NOT NULL
                  AND d.id_usuario <> NEW.id_usuario;   -- nunca al autor
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        // El trigger tr_notificar_nuevo_comentario ya existe; solo reemplazamos la función.
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_nueva_incidencia ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_nueva_incidencia();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_asignacion ON asignaciones_incidencia;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_asignacion();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_cambio_estado ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_cambio_estado();');

        // Restaura el trigger de comentario a su versión original (solo reportador).
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_nuevo_comentario()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_id_reportador     BIGINT;
                v_nombre_incidencia VARCHAR(255);
            BEGIN
                SELECT id_usuario, nombre_incidencia
                INTO v_id_reportador, v_nombre_incidencia
                FROM incidencias
                WHERE id_incidencia = NEW.id_incidencia;

                IF NEW.id_usuario <> v_id_reportador THEN
                    INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                    VALUES (v_id_reportador, NEW.id_incidencia, 'COMENTARIO',
                            'Nuevo comentario en tu incidencia: ' || v_nombre_incidencia);
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }
};
