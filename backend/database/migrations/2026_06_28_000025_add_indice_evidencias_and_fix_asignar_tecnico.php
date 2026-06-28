<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índice para acelerar el conteo de evidencias por incidencia (trigger fn_limite_evidencias y EvidenciaController).
        DB::statement('CREATE INDEX IF NOT EXISTS idx_evidencias_incidencia ON evidencias(id_incidencia)');

        // Reemplaza el procedure para que filtre usuarios soft-deleted (no asignar a un técnico borrado).
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

            -- Validación 2: ¿el usuario existe (no borrado) y tiene rol técnico o admin?
            SELECT r.nombre_rol INTO v_rol_usuario
            FROM users u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id = p_id_usuario AND u.deleted_at IS NULL;

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_evidencias_incidencia');

        // Restaura el procedure sin el filtro de deleted_at (versión original).
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
            SELECT COUNT(*) INTO v_existe_incidencia
            FROM incidencias WHERE id_incidencia = p_id_incidencia;

            IF v_existe_incidencia = 0 THEN
                RAISE EXCEPTION 'La incidencia % no existe.', p_id_incidencia;
            END IF;

            SELECT r.nombre_rol INTO v_rol_usuario
            FROM users u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id = p_id_usuario;

            IF v_rol_usuario NOT IN ('tecnico', 'admin') THEN
                RAISE EXCEPTION 'El usuario % no tiene permisos para ser asignado.', p_id_usuario;
            END IF;

            SELECT COUNT(*) INTO v_ya_asignado
            FROM asignaciones_incidencia
            WHERE id_incidencia = p_id_incidencia AND id_usuario = p_id_usuario;

            IF v_ya_asignado > 0 THEN
                RAISE EXCEPTION 'El usuario % ya está asignado a esta incidencia.', p_id_usuario;
            END IF;

            IF p_rol NOT IN ('RESPONSABLE', 'APOYO') THEN
                RAISE EXCEPTION 'El rol % no es válido. Use RESPONSABLE o APOYO.', p_rol;
            END IF;

            INSERT INTO asignaciones_incidencia (id_incidencia, id_usuario, rol_asignado)
            VALUES (p_id_incidencia, p_id_usuario, p_rol);
        END;
        \$\$;
        ");
    }
};
