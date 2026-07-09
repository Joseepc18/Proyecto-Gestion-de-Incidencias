<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Procedimiento asignar_tecnico: valida (incidencia existe, rol técnico, sin duplicado, RESPONSABLE/APOYO) y asigna el técnico.
        DB::unprepared("
        CREATE OR REPLACE PROCEDURE asignar_tecnico(
            p_id_incidencia BIGINT,
            p_id_usuario    BIGINT,
            p_rol           VARCHAR
        )
        LANGUAGE plpgsql
        AS \$\$
        DECLARE
            v_existe_incidencia  INT;
            v_rol_usuario        VARCHAR;
            v_ya_asignado        INT;
        BEGIN
            -- Validación 1: ¿existe la incidencia?
            SELECT COUNT(*) INTO v_existe_incidencia
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe_incidencia = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            -- Validación 2: ¿el usuario existe (no borrado) y tiene rol técnico?
            SELECT r.nombre_rol INTO v_rol_usuario
            FROM users u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id = p_id_usuario AND u.deleted_at IS NULL;

            -- Si no encontró fila, v_rol_usuario es NULL (NULL <> 'x' no dispara, hay que chequearlo aparte).
            IF v_rol_usuario IS NULL THEN
                RAISE EXCEPTION 'El usuario % no existe o fue eliminado.', p_id_usuario;
            END IF;

            IF v_rol_usuario <> 'tecnico' THEN
                RAISE EXCEPTION 'El usuario % no tiene permisos para ser asignado.', p_id_usuario;
            END IF;

            -- Validación 3: ¿ya está asignado a esta incidencia?
            SELECT COUNT(*) INTO v_ya_asignado
            FROM asignaciones_incidencia
            WHERE id_incidencia = p_id_incidencia AND id_usuario = p_id_usuario;

            IF v_ya_asignado > 0 THEN
                RAISE EXCEPTION 'El usuario % ya está asignado a esta incidencia.', p_id_usuario;
            END IF;

            -- Validación 4: ¿el rol es válido?
            IF p_rol NOT IN ('RESPONSABLE', 'APOYO') THEN
                RAISE EXCEPTION 'El rol % no es válido. Use RESPONSABLE o APOYO.', p_rol;
            END IF;

            -- Todo válido: inserta la asignación
            INSERT INTO asignaciones_incidencia (id_incidencia, id_usuario, rol_asignado)
            VALUES (p_id_incidencia, p_id_usuario, p_rol);

        END;
        \$\$;
        ");

        // Procedimiento resolver_incidencia: pasa a RESUELTO (los triggers hacen historial y fecha); las notificaciones las emite el listener EnviarNotificacionCambioEstado, ya no se insertan aquí.
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
        BEGIN
            -- Validación 1: ¿existe la incidencia?
            SELECT COUNT(*) INTO v_existe
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            -- Validación 2: ¿ya está resuelta? FOR UPDATE bloquea la fila: dos resolvers concurrentes se serializan y el perdedor recae aquí viendo 'RESUELTO', sin duplicar historial ni evento.
            SELECT estado_incidencia INTO v_estado_actual
            FROM incidencias WHERE id_incidencia = p_id_incidencia
            FOR UPDATE;

            IF v_estado_actual = 'RESUELTO' THEN
                RAISE EXCEPTION 'La incidencia % ya está resuelta.', p_id_incidencia;
            END IF;

            -- Publica el actor para el trigger de historial (local a la transacción).
            PERFORM set_config('app.actor_id', p_id_usuario::text, true);

            -- Cambia el estado a RESUELTO; los triggers de fecha e historial hacen el resto automáticamente.
            UPDATE incidencias
            SET estado_incidencia = 'RESUELTO',
                updated_at = NOW()
            WHERE id_incidencia = p_id_incidencia;

        END;
        \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS asignar_tecnico(BIGINT, BIGINT, VARCHAR);');
        DB::unprepared('DROP PROCEDURE IF EXISTS resolver_incidencia(BIGINT, BIGINT);');
    }
};
