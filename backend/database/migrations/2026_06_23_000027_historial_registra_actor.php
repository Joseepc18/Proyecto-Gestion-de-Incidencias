<?php

use Illuminate\Database\Migrations\Migration;

// H-02: el historial guardaba al dueño como autor del cambio. Ahora el trigger
// lee al ejecutor desde la variable de sesión app.actor_id que pone la app.
return new class extends Migration
{
    public function up(): void
    {
        // Trigger: usa el actor de la variable de sesión, o el dueño si no está.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_registrar_cambio_estado()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_actor BIGINT;
            BEGIN
                IF NEW.estado_incidencia <> OLD.estado_incidencia THEN
                    -- true evita el error si la variable no fue seteada.
                    v_actor := NULLIF(current_setting('app.actor_id', true), '')::BIGINT;
                    IF v_actor IS NULL THEN
                        v_actor := NEW.id_usuario;
                    END IF;

                    INSERT INTO historial_estados (id_incidencia, id_usuario, estado_anterior, estado_nuevo)
                    VALUES (NEW.id_incidencia, v_actor, OLD.estado_incidencia, NEW.estado_incidencia);
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Procedimiento: publica el actor para el trigger antes del UPDATE.
        DB::unprepared("
        CREATE OR REPLACE PROCEDURE resolver_incidencia(
            p_id_incidencia BIGINT,
            p_id_usuario    BIGINT
        )
        LANGUAGE plpgsql
        AS \$\$
        DECLARE
            v_existe        INT;
            v_estado_actual VARCHAR;
            v_nombre        VARCHAR;
            v_reportador_id BIGINT;
            v_tecnico_id    BIGINT;
        BEGIN
            SELECT COUNT(*) INTO v_existe
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            SELECT estado_incidencia, nombre_incidencia, id_usuario
            INTO v_estado_actual, v_nombre, v_reportador_id
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_estado_actual = 'RESUELTO' THEN
                RAISE EXCEPTION 'La incidencia % ya está resuelta.', p_id_incidencia;
            END IF;

            -- Actor para el trigger de historial (local a la transacción).
            PERFORM set_config('app.actor_id', p_id_usuario::text, true);

            UPDATE incidencias
            SET estado_incidencia = 'RESUELTO'
            WHERE id_incidencia = p_id_incidencia;

            INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
            VALUES (v_reportador_id, p_id_incidencia, 'CAMBIO_ESTADO',
                    'Tu incidencia ha sido resuelta: ' || v_nombre);

            FOR v_tecnico_id IN
                SELECT id_usuario FROM asignaciones_incidencia WHERE id_incidencia = p_id_incidencia
            LOOP
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                VALUES (v_tecnico_id, p_id_incidencia, 'CAMBIO_ESTADO',
                        'La incidencia ha sido marcada como resuelta: ' || v_nombre);
            END LOOP;
        END;
        \$\$;
        ");
    }

    public function down(): void
    {
        // Trigger original: atribuía el cambio al dueño de la incidencia.
        DB::unprepared('
        CREATE OR REPLACE FUNCTION fn_registrar_cambio_estado()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.estado_incidencia <> OLD.estado_incidencia THEN
                    INSERT INTO historial_estados (id_incidencia, id_usuario, estado_anterior, estado_nuevo)
                    VALUES (NEW.id_incidencia, NEW.id_usuario, OLD.estado_incidencia, NEW.estado_incidencia);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Procedimiento original: sin set_config.
        DB::unprepared("
        CREATE OR REPLACE PROCEDURE resolver_incidencia(
            p_id_incidencia BIGINT,
            p_id_usuario    BIGINT
        )
        LANGUAGE plpgsql
        AS \$\$
        DECLARE
            v_existe        INT;
            v_estado_actual VARCHAR;
            v_nombre        VARCHAR;
            v_reportador_id BIGINT;
            v_tecnico_id    BIGINT;
        BEGIN
            SELECT COUNT(*) INTO v_existe
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            SELECT estado_incidencia, nombre_incidencia, id_usuario
            INTO v_estado_actual, v_nombre, v_reportador_id
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_estado_actual = 'RESUELTO' THEN
                RAISE EXCEPTION 'La incidencia % ya está resuelta.', p_id_incidencia;
            END IF;

            UPDATE incidencias
            SET estado_incidencia = 'RESUELTO'
            WHERE id_incidencia = p_id_incidencia;

            INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
            VALUES (v_reportador_id, p_id_incidencia, 'CAMBIO_ESTADO',
                    'Tu incidencia ha sido resuelta: ' || v_nombre);

            FOR v_tecnico_id IN
                SELECT id_usuario FROM asignaciones_incidencia WHERE id_incidencia = p_id_incidencia
            LOOP
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                VALUES (v_tecnico_id, p_id_incidencia, 'CAMBIO_ESTADO',
                        'La incidencia ha sido marcada como resuelta: ' || v_nombre);
            END LOOP;
        END;
        \$\$;
        ");
    }
};
