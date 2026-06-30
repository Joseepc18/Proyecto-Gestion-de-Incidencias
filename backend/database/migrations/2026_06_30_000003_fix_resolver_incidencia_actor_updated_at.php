<?php

use Illuminate\Database\Migrations\Migration;

// C5: no auto-notificar a quien resuelve (alinea con fn_notificar_cambio_estado, que ya excluye al actor).
// C6: refrescar updated_at en el UPDATE (vía Eloquent sí se actualizaba; vía SP quedaba viejo).
return new class extends Migration
{
    public function up(): void
    {
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
            -- Validación 1: ¿existe la incidencia?
            SELECT COUNT(*) INTO v_existe
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            -- Validación 2: ¿ya está resuelta?
            SELECT estado_incidencia, nombre_incidencia, id_usuario
            INTO v_estado_actual, v_nombre, v_reportador_id
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_estado_actual = 'RESUELTO' THEN
                RAISE EXCEPTION 'La incidencia % ya está resuelta.', p_id_incidencia;
            END IF;

            -- Publica el actor para el trigger de historial (local a la transacción).
            PERFORM set_config('app.actor_id', p_id_usuario::text, true);

            -- Cambia el estado a RESUELTO y refresca updated_at
            -- El trigger tr_fecha_resolucion llena fecha_resolucion automáticamente
            -- El trigger tr_cambio_estado_incidencias guarda el historial automáticamente
            UPDATE incidencias
            SET estado_incidencia = 'RESUELTO',
                updated_at = NOW()
            WHERE id_incidencia = p_id_incidencia;

            -- Notifica al reportador, salvo que sea quien resuelve.
            IF v_reportador_id IS DISTINCT FROM p_id_usuario THEN
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                VALUES (v_reportador_id, p_id_incidencia, 'CAMBIO_ESTADO',
                        'Tu incidencia ha sido resuelta: ' || v_nombre);
            END IF;

            -- Notifica a cada técnico asignado, menos al actor.
            FOR v_tecnico_id IN
                SELECT id_usuario FROM asignaciones_incidencia
                WHERE id_incidencia = p_id_incidencia
                  AND id_usuario IS DISTINCT FROM p_id_usuario
            LOOP
                INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                VALUES (v_tecnico_id, p_id_incidencia, 'CAMBIO_ESTADO',
                        'La incidencia ha sido marcada como resuelta: ' || v_nombre);
            END LOOP;

        END;
        \$\$;
        ");
    }

    // Restaura la versión anterior (sin excluir al actor, sin updated_at).
    public function down(): void
    {
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
};
