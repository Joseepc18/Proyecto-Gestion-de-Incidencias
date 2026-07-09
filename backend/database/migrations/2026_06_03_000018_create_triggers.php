<?php

use Illuminate\Database\Migrations\Migration;

// Triggers del sistema, en su versión final (definición completa, sin ALTERs posteriores).
// Las notificaciones ya no viven en triggers: pasaron a Events/Listeners + Notifications de Laravel.
return new class extends Migration
{
    public function up(): void
    {
        // fn_registrar_cambio_estado: guarda cada cambio de estado atribuyéndolo al EJECUTOR (app.actor_id), no al dueño; si nadie lo fijó queda NULL (lo hizo el sistema).
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

        // fn_fecha_resolucion: setea/limpia fecha_resolucion según el estado; al archivar (RESUELTO->CERRADO) NO se limpia (la usa el auto-archivado y es dato histórico).
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

        // fn_limite_evidencias: tope de 3 fotos por tipo (REPORTE y RESOLUCION); v_limite debe coincidir con Incidencia::LIMITE_EVIDENCIAS_POR_TIPO.
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
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_cambio_estado_incidencias ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_registrar_cambio_estado();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_fecha_resolucion ON incidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_fecha_resolucion();');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_limite_evidencias ON evidencias;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_limite_evidencias();');
    }
};
