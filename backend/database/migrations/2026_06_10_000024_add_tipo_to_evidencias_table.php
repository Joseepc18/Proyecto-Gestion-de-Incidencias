<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Distingue la foto del REPORTE (el problema) de la de RESOLUCION (la solución).
        DB::statement("
            ALTER TABLE evidencias
            ADD COLUMN tipo_evidencia VARCHAR(50) NOT NULL DEFAULT 'REPORTE'
            CHECK (tipo_evidencia IN ('REPORTE','RESOLUCION'));
        ");

        // Límite por tipo: 3 fotos de REPORTE, 1 de RESOLUCION.
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaura el límite original (5 en total, sin distinguir tipo).
        DB::unprepared("
        CREATE OR REPLACE FUNCTION fn_limite_evidencias()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF (SELECT COUNT(*) FROM evidencias WHERE id_incidencia = NEW.id_incidencia) >= 5 THEN
                    RAISE EXCEPTION 'La incidencia ya tiene el máximo de 5 evidencias permitidas.';
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement('ALTER TABLE evidencias DROP COLUMN IF EXISTS tipo_evidencia;');
    }
};
