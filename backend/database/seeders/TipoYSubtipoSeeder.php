<?php

namespace Database\Seeders;

use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use Illuminate\Database\Seeder;

class TipoYSubtipoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── VIALIDAD Y TRANSPORTE ───────────────────────────────────────────────
        $t = TipoIncidencia::firstOrCreate(['nombre_tipo_incidencia' => 'Vialidad y Transporte']);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Baches / Huecos en la calzada',                  'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Fisuras o grietas extensas',                     'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Hundimiento del asfalto',                        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Señales de tránsito dañadas, caídas o faltantes', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Pintura de pasos cebra o líneas de carril borradas', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Espejos de seguridad convexos rotos',            'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Semáforo apagado por completo',                  'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Luz de semáforo fundida',                        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Semáforo desincronizado',                        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Aceras rotas, levantadas por raíces o con obstáculos', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Falta de rampas de accesibilidad o rampas dañadas',   'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Obstáculos en la ciclovía',                     'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Falta de delimitación o bolardos de ciclovía rotos', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);

        // ─── SERVICIOS PÚBLICOS ──────────────────────────────────────────────────
        $t = TipoIncidencia::firstOrCreate(['nombre_tipo_incidencia' => 'Servicios Públicos']);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Luminaria apagada de noche (calle a oscuras)',   'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Luminaria encendida de día',                    'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Poste de luz chocado, inclinado o en peligro de caer', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Cables eléctricos expuestos o colgando a baja altura', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Fuga de agua potable en la vía pública',        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Falta de suministro (corte de agua generalizado)', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Alcantarilla tapada / Inundación por lluvias',  'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Falta de tapa de alcantarilla',                 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Mal olor proveniente del alcantarillado',       'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Cables de internet o telefonía caídos en la calle', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Cajas de distribución de telecomunicaciones abiertas o destruidas', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);

        // ─── ASEO URBANO ─────────────────────────────────────────────────────────
        $t = TipoIncidencia::firstOrCreate(['nombre_tipo_incidencia' => 'Aseo Urbano']);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Incumplimiento del horario o ruta de recolección de basura', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Acumulación de basura en esquinas o aceras',    'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Contenedor público roto, volcado o quemado',    'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Contenedor desbordado de residuos',             'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Escombros o muebles abandonados en la vía pública', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Presencia de vidrios rotos o sustancias peligrosas en la calle', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Pintadas o graffitis vandálicos en propiedad pública', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);

        // ─── PARQUES Y MEDIO AMBIENTE ────────────────────────────────────────────
        $t = TipoIncidencia::firstOrCreate(['nombre_tipo_incidencia' => 'Parques y Medio Ambiente']);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Árbol o rama grande con riesgo de caer',        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Ramas que obstruyen cables eléctricos o señales', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Árbol caído en la vía pública',                 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Necesidad de poda preventiva',                  'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Juegos infantiles rotos o peligrosos',          'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Bancos de parque destruidos o faltantes',       'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Máquinas de ejercicio biosaludables dañadas',   'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Falta de riego o césped completamente seco',    'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Plagas en la vegetación del parque',            'id_tipo_incidencia' => $t->id_tipo_incidencia]);

        // ─── SEGURIDAD Y CONVIVENCIA ─────────────────────────────────────────────
        $t = TipoIncidencia::firstOrCreate(['nombre_tipo_incidencia' => 'Seguridad y Convivencia']);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Vehículo abandonado en la vía pública',         'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Vehículo mal estacionado (bloqueando rampas o aceras)', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Animales callejeros heridos o en peligro',      'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Presencia de jaurías de perros agresivas',      'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Animales muertos en la vía pública',            'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Ruido excesivo (fiestas, locales o talleres fuera de horario)', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Quema de basura o maleza al aire libre',        'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Emisión ilegal de gases, humos o contaminantes', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Zonas con sospecha de actividad delictiva o vandalismo recurrente', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
        SubtipoIncidencia::firstOrCreate(['nombre_subtipo_incidencia' => 'Consumo de sustancias prohibidas en parques públicos', 'id_tipo_incidencia' => $t->id_tipo_incidencia]);
    }
}
