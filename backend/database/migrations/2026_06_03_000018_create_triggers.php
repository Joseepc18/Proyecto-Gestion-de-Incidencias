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
        // ─── TRIGGER 1: Auditoría de cambios de estado ───────────────────────────
        // Guarda en historial_estados cada vez que el estado de una incidencia
        // cambia, registrando el estado anterior y el nuevo para trazabilidad.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_registrar_cambio_estado()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Solo actúa si el estado realmente cambió
                IF NEW.estado_incidencia <> OLD.estado_incidencia THEN
                    -- Guarda la transición: de qué estado venía y a cuál llegó
                    INSERT INTO historial_estados (id_incidencia, id_usuario, estado_anterior, estado_nuevo)
                    VALUES (NEW.id_incidencia, NEW.id_usuario, OLD.estado_incidencia, NEW.estado_incidencia);
                END IF;
                RETURN NEW; -- permite que el UPDATE continúe normalmente
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        // Dispara la función después de cada UPDATE en incidencias, una vez por fila
        DB::unprepared("
        CREATE TRIGGER tr_cambio_estado_incidencias
            AFTER UPDATE ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_registrar_cambio_estado();
        ");

        // ─── TRIGGER 2: Fecha de resolución automática ───────────────────────────
        // Gestiona fecha_resolucion automáticamente según el estado. Usa BEFORE
        // para poder modificar la fila antes de que se escriba en disco.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_fecha_resolucion()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Si pasa a RESUELTO, registra el momento exacto
                IF NEW.estado_incidencia = 'RESUELTO' AND OLD.estado_incidencia <> 'RESUELTO' THEN
                    NEW.fecha_resolucion = NOW();
                END IF;
                -- Si se revierte desde RESUELTO, borra la fecha
                IF NEW.estado_incidencia <> 'RESUELTO' AND OLD.estado_incidencia = 'RESUELTO' THEN
                    NEW.fecha_resolucion = NULL;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        // BEFORE: intercepta la fila antes de guardarse para poder modificarla
        DB::unprepared("
        CREATE TRIGGER tr_fecha_resolucion
            BEFORE UPDATE ON incidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_fecha_resolucion();
        ");

        // ─── TRIGGER 3: Notificación al ciudadano por nuevo comentario ───────────
        // Al insertar un comentario notifica al reportador de la incidencia.
        // No notifica si quien comenta es el mismo que reportó.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_notificar_nuevo_comentario()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_id_reportador     BIGINT;
                v_nombre_incidencia VARCHAR(255);
            BEGIN
                -- Obtiene quién reportó la incidencia y su nombre
                SELECT id_usuario, nombre_incidencia
                INTO v_id_reportador, v_nombre_incidencia
                FROM incidencias
                WHERE id_incidencia = NEW.id_incidencia;

                -- Solo notifica si quien comenta NO es el reportador
                IF NEW.id_usuario <> v_id_reportador THEN
                    INSERT INTO notificaciones (id_usuario, id_incidencia, tipo_notificacion, mensaje_notificacion)
                    VALUES (v_id_reportador, NEW.id_incidencia, 'COMENTARIO',
                            'Nuevo comentario en tu incidencia: ' || v_nombre_incidencia);
                END IF;

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        // Dispara al insertar un comentario nuevo en la tabla comentarios
        DB::unprepared("
        CREATE TRIGGER tr_notificar_nuevo_comentario
            AFTER INSERT ON comentarios
            FOR EACH ROW
            EXECUTE FUNCTION fn_notificar_nuevo_comentario();
        ");

        // ─── TRIGGER 4: Límite máximo de evidencias por incidencia ───────────────
        // Antes de insertar una evidencia verifica que no supere el máximo de 5.
        // Si ya tiene 5, cancela el INSERT con un error.
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_limite_evidencias()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Cuenta cuántas evidencias ya tiene esta incidencia
                IF (SELECT COUNT(*) FROM evidencias WHERE id_incidencia = NEW.id_incidencia) >= 5 THEN
                    -- Cancela el INSERT y lanza un error
                    RAISE EXCEPTION 'La incidencia ya tiene el máximo de 5 evidencias permitidas.';
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        // BEFORE: intercepta el INSERT antes de que llegue a la tabla
        DB::unprepared("
        CREATE TRIGGER tr_limite_evidencias
            BEFORE INSERT ON evidencias
            FOR EACH ROW
            EXECUTE FUNCTION fn_limite_evidencias();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_cambio_estado_incidencias ON incidencias;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_registrar_cambio_estado();");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_fecha_resolucion ON incidencias;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_fecha_resolucion();");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_notificar_nuevo_comentario ON comentarios;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_notificar_nuevo_comentario();");
        DB::unprepared("DROP TRIGGER IF EXISTS tr_limite_evidencias ON evidencias;");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_limite_evidencias();");
    }
};
