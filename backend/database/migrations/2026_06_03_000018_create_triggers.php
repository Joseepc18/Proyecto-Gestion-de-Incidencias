<?php

use Illuminate\Database\Migrations\Migration;

// Los 7 triggers del sistema, en su versión final (definición completa, sin ALTERs posteriores).
return new class extends Migration
{
    public function up(): void
    {
        // 1) fn_registrar_cambio_estado: guarda cada cambio de estado atribuyéndolo al EJECUTOR (app.actor_id), no al dueño.
        // Si nadie fijó app.actor_id (el job de archivado automático no lo hace), queda NULL = lo hizo el sistema,
        // no se le atribuye al reportador (id_usuario es nullable en historial_estados justo para este caso).
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_registrar_cambio_estado()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_actor BIGINT;
            BEGIN
                IF NEW.estado_incidencia <> OLD.estado_incidencia THEN
                    -- true evita el error si la variable no fue seteada.
                    v_actor := NULLIF(current_setting('app.actor_id', true), '')::BIGINT;

                    INSERT INTO historial_estados (id_incidencia, id_usuario, estado_anterior, estado_nuevo)
                    VALUES (NEW.id_incidencia, v_actor, OLD.estado_incidencia, NEW.estado_incidencia);
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_cambio_estado_incidencias ON incidencias;');
        DB::unprepared('
        CREATE TRIGGER tr_cambio_estado_incidencias
            AFTER UPDATE ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_registrar_cambio_estado();
        ');

        // 2) fn_fecha_resolucion: setea/limpia fecha_resolucion según el estado (BEFORE).
        // Al archivar (RESUELTO -> CERRADO) NO se limpia: el job de auto-archivado la necesita (fecha_resolucion
        // <= now() - 24h) y además es la fecha real en que se resolvió, se conserva como dato histórico.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_fecha_resolucion()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.estado_incidencia = 'RESUELTO' AND OLD.estado_incidencia <> 'RESUELTO' THEN
                    NEW.fecha_resolucion = NOW();
                END IF;
                IF NEW.estado_incidencia NOT IN ('RESUELTO', 'CERRADO') AND OLD.estado_incidencia = 'RESUELTO' THEN
                    NEW.fecha_resolucion = NULL;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_fecha_resolucion ON incidencias;');
        DB::unprepared('
        CREATE TRIGGER tr_fecha_resolucion
            BEFORE UPDATE ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_fecha_resolucion();
        ');

        // 3) fn_notificar_nuevo_comentario: avisa al chat (reportador + admins + responsable) menos al autor; consolida en la notificación sin leer existente (uq_notif_comentario_pendiente).
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

                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion, contador)
                SELECT DISTINCT d.id_usuario, NEW.id_incidencia, 'COMENTARIO',
                       'Nuevo comentario en la incidencia: ' || v_nombre, 1
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
                  AND d.id_usuario <> NEW.id_usuario   -- nunca al autor
                ON CONFLICT (id_usuario, id_incidencia)
                    WHERE tipo_notificacion = 'COMENTARIO' AND estado_lectura = false
                DO UPDATE SET
                    contador             = notificaciones.contador + 1,
                    mensaje_notificacion = (notificaciones.contador + 1) || ' comentarios nuevos en la incidencia: ' || v_nombre,
                    created_at           = NOW(),
                    updated_at           = NOW();
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_nuevo_comentario ON comentarios;');
        DB::unprepared('
        CREATE TRIGGER tr_notificar_nuevo_comentario
            AFTER INSERT ON comentarios
            FOR EACH ROW
            EXECUTE FUNCTION fn_notificar_nuevo_comentario();
        ');

        // 4) fn_limite_evidencias: tope de 3 fotos por tipo (REPORTE y RESOLUCION) (BEFORE INSERT).
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_limite_evidencias()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_limite   INT := 3;
                v_actuales INT;
            BEGIN
                SELECT COUNT(*) INTO v_actuales
                FROM evidencias
                WHERE id_incidencia = NEW.id_incidencia
                  AND tipo_evidencia = NEW.tipo_evidencia;

                IF v_actuales >= v_limite THEN
                    RAISE EXCEPTION 'Máximo % evidencia(s) de tipo %.', v_limite, NEW.tipo_evidencia;
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        DB::unprepared('DROP TRIGGER IF EXISTS tr_limite_evidencias ON evidencias;');
        DB::unprepared('
        CREATE TRIGGER tr_limite_evidencias
            BEFORE INSERT ON evidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_limite_evidencias();
        ');

        // 5) fn_notificar_nueva_incidencia: al crear una incidencia, avisa a los administradores.
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

        // 6) fn_notificar_asignacion: avisa al técnico asignado y, si es RESPONSABLE, también al reportador.
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

        // 7) fn_notificar_cambio_estado: en cambios distintos de RESUELTO avisa a reportador y técnicos menos al actor;
        // solo RESUELTO -> EN_PROCESO es la reapertura real (SOLICITUD_REAPERTURA); RESUELTO -> CERRADO (archivado)
        // usa el mensaje genérico de CAMBIO_ESTADO, no el de reapertura.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_cambio_estado()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_actor BIGINT;
                v_tipo VARCHAR;
                v_msg_reportador VARCHAR;
                v_msg_tecnicos VARCHAR;
            BEGIN
                IF NEW.estado_incidencia <> OLD.estado_incidencia
                   AND NEW.estado_incidencia <> 'RESUELTO' THEN
                    v_actor := NULLIF(current_setting('app.actor_id', true), '')::BIGINT;

                    IF OLD.estado_incidencia = 'RESUELTO' AND NEW.estado_incidencia = 'EN_PROCESO' THEN
                        v_tipo := 'SOLICITUD_REAPERTURA';
                        v_msg_reportador := 'Tu incidencia fue reabierta: ' || NEW.nombre_incidencia;
                        v_msg_tecnicos := 'La incidencia fue reabierta: ' || NEW.nombre_incidencia;
                    ELSE
                        v_tipo := 'CAMBIO_ESTADO';
                        v_msg_reportador := 'Tu incidencia cambió a ' || NEW.estado_incidencia || ': ' || NEW.nombre_incidencia;
                        v_msg_tecnicos := 'La incidencia cambió a ' || NEW.estado_incidencia || ': ' || NEW.nombre_incidencia;
                    END IF;

                    -- Al reportador (IS DISTINCT FROM trata bien el actor NULL).
                    IF NEW.id_usuario IS DISTINCT FROM v_actor THEN
                        INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                        VALUES (NEW.id_usuario, NEW.id_incidencia, v_tipo, v_msg_reportador);
                    END IF;

                    -- A los técnicos asignados (responsable y apoyo), menos el actor.
                    INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                    SELECT a.id_usuario, NEW.id_incidencia, v_tipo, v_msg_tecnicos
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
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_cambio_estado_incidencias ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_registrar_cambio_estado();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_fecha_resolucion ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_fecha_resolucion();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_nuevo_comentario ON comentarios;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_nuevo_comentario();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_limite_evidencias ON evidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_limite_evidencias();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_nueva_incidencia ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_nueva_incidencia();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_asignacion ON asignaciones_incidencia;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_asignacion();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_notificar_cambio_estado ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_notificar_cambio_estado();');
    }
};
