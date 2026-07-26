<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Última capa del conflicto de interés: el técnico que reportó una incidencia no puede quedar asignado a ella.
// Repite entero el cuerpo del procedimiento (create_procedimientos_almacenados ya corrió en producción y no
// se vuelve a aplicar); CREATE OR REPLACE lo deja idéntico corra una vez o las dos.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
        CREATE OR REPLACE PROCEDURE asignar_tecnico(
            p_id_incidencia BIGINT,
            p_id_usuario    BIGINT,
            p_rol           VARCHAR
        )
        LANGUAGE plpgsql
        AS \$\$
        DECLARE
            v_autor_incidencia   BIGINT;
            v_existe_incidencia  INT;
            v_rol_usuario        VARCHAR;
            v_ya_asignado        INT;
        BEGIN
            -- Validación 1: ¿existe la incidencia? (de paso guarda quién la reportó, para la validación 3)
            SELECT COUNT(*), MAX(id_usuario) INTO v_existe_incidencia, v_autor_incidencia
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

            -- Validación 3: conflicto de interés — quien reportó la incidencia no la atiende.
            IF v_autor_incidencia = p_id_usuario THEN
                RAISE EXCEPTION 'El usuario % reportó esta incidencia y no puede atenderla.', p_id_usuario;
            END IF;

            -- Validación 4: ¿ya está asignado a esta incidencia?
            SELECT COUNT(*) INTO v_ya_asignado
            FROM asignaciones_incidencia
            WHERE id_incidencia = p_id_incidencia AND id_usuario = p_id_usuario;

            IF v_ya_asignado > 0 THEN
                RAISE EXCEPTION 'El usuario % ya está asignado a esta incidencia.', p_id_usuario;
            END IF;

            -- Validación 5: ¿el rol es válido?
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

    public function down(): void
    {
        // Sin vuelta atrás propia: quitar la validación exigiría reescribir el procedimiento entero
        // con el cuerpo viejo, que es justo lo que create_procedimientos_almacenados vuelve a crear.
    }
};
