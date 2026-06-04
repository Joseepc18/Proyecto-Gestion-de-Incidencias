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
        // PROCEDIMIENTO 1: asignar_tecnico
        // Valida y asigna un técnico a una incidencia. Verifica que la incidencia
        // exista, que el usuario tenga rol válido, que no esté ya asignado y que
        // el rol sea RESPONSABLE o APOYO antes de insertar la asignación.
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

            -- Validación 2: ¿el usuario tiene rol técnico o admin?
            SELECT r.nombre_rol INTO v_rol_usuario
            FROM users u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id = p_id_usuario;

            IF v_rol_usuario NOT IN ('tecnico', 'admin') THEN
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

        // PROCEDIMIENTO 2: resolver_incidencia
        // Cambia el estado a RESUELTO y notifica al reportador y a todos los técnicos
        // asignados. Los triggers de historial y fecha_resolucion actúan automáticamente.
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

            -- Cambia el estado a RESUELTO
            -- El trigger tr_fecha_resolucion llena fecha_resolucion automáticamente
            -- El trigger tr_cambio_estado_incidencias guarda el historial automáticamente
            UPDATE incidencias
            SET estado_incidencia = 'RESUELTO'
            WHERE id_incidencia = p_id_incidencia;

            -- Notifica al ciudadano reportador
            INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
            VALUES (v_reportador_id, p_id_incidencia, 'CAMBIO_ESTADO',
                    'Tu incidencia ha sido resuelta: ' || v_nombre);

            -- Notifica a cada técnico asignado
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS asignar_tecnico(BIGINT, BIGINT, VARCHAR);");
        DB::unprepared("DROP PROCEDURE IF EXISTS resolver_incidencia(BIGINT, BIGINT);");
    }
};