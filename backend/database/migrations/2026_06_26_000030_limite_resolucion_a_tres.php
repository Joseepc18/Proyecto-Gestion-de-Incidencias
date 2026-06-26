<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // El técnico puede subir varias fotos (avance + resolución), no solo una:
    // el límite de RESOLUCION pasa de 1 a 3 (igual que REPORTE).
    public function up(): void
    {
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
    }

    // Restaura el límite anterior: 3 de REPORTE, 1 de RESOLUCION.
    public function down(): void
    {
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_limite_evidencias()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_limite   INT;
                v_actuales INT;
            BEGIN
                IF NEW.tipo_evidencia = 'RESOLUCION' THEN
                    v_limite := 1;
                ELSE
                    v_limite := 3;
                END IF;

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
    }
};
