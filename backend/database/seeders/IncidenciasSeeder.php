<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Incidencia;
use App\Models\Rol;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class IncidenciasSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sistema.com')->first();
        $tecnico = User::firstOrCreate(
            ['email' => 'tecnico@sistema.com'],
            [
                'name' => 'Técnico Prueba',
                'password' => Hash::make('password123'),
                'id_rol' => Rol::where('nombre_rol', 'tecnico')->first()->id_rol ?? null,
            ]
        );
        $normal = User::firstOrCreate(
            ['email' => 'normal@sistema.com'],
            [
                'name' => 'Usuario Normal',
                'password' => Hash::make('password123'),
                'id_rol' => Rol::where('nombre_rol', 'normal')->first()->id_rol ?? null,
            ]
        );

        $santaElena = Ciudad::where('nombre_ciudad', 'Santa Elena')->first();
        $salinas = Ciudad::where('nombre_ciudad', 'Salinas')->first();
        $laLibertad = Ciudad::where('nombre_ciudad', 'La Libertad')->first();
        $guayaquil = Ciudad::where('nombre_ciudad', 'Guayaquil')->first();

        $baches = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Baches%')->first();
        $semaforo = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Semáforo apagado%')->first();
        $luminaria = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Luminaria apagada%')->first();
        $fugaAgua = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Fuga de agua%')->first();
        $alcantarilla = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Alcantarilla tapada%')->first();
        $basura = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Acumulación de basura%')->first();
        $escombros = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Escombros%')->first();
        $arbol = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Árbol o rama grande%')->first();
        $juegos = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Juegos infantiles%')->first();
        $vehiculo = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Vehículo abandonado%')->first();
        $ruido = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Ruido excesivo%')->first();
        $aceras = SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', 'Aceras rotas%')->first();

        $datos = [
            [
                'nombre_incidencia' => 'Bache enorme en Av. Principal',
                'descripcion_incidencia' => 'Bache de 60cm de diámetro frente al mercado central, varios vehículos ya dañaron sus llantas.',
                'latitud_incidencia' => -2.2267,
                'longitud_incidencia' => -80.8593,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'ALTA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $baches->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Semáforo apagado en cruce escolar',
                'descripcion_incidencia' => 'El semáforo del cruce de la escuela fiscal lleva 3 días sin funcionar.',
                'latitud_incidencia' => -2.2280,
                'longitud_incidencia' => -80.8610,
                'estado_incidencia' => 'EN_PROCESO',
                'prioridad_incidencia' => 'ALTA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $semaforo->id_subtipo_incidencia,
                'id_usuario' => $admin->id,
            ],
            [
                'nombre_incidencia' => 'Luminarias apagadas en malecón de Salinas',
                'descripcion_incidencia' => 'Tres luminarias consecutivas apagadas en el malecón, zona peligrosa de noche.',
                'latitud_incidencia' => -2.2120,
                'longitud_incidencia' => -80.9551,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'MEDIA',
                'id_ciudad' => $salinas->id_ciudad,
                'id_subtipo_incidencia' => $luminaria->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Fuga de agua en calle 10 de Agosto',
                'descripcion_incidencia' => 'Tubería rota, el agua corre por toda la calle desde hace 2 días.',
                'latitud_incidencia' => -2.2300,
                'longitud_incidencia' => -80.9100,
                'estado_incidencia' => 'RESUELTO',
                'prioridad_incidencia' => 'ALTA',
                'id_ciudad' => $laLibertad->id_ciudad,
                'id_subtipo_incidencia' => $fugaAgua->id_subtipo_incidencia,
                'id_usuario' => $tecnico->id,
            ],
            [
                'nombre_incidencia' => 'Alcantarilla desbordada tras lluvias',
                'descripcion_incidencia' => 'La alcantarilla de la esquina se desbordó con las lluvias del fin de semana.',
                'latitud_incidencia' => -2.2245,
                'longitud_incidencia' => -80.8580,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'ALTA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $alcantarilla->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Basura acumulada en parque central',
                'descripcion_incidencia' => 'Llevan una semana sin recoger la basura del parque, hay mal olor.',
                'latitud_incidencia' => -2.2150,
                'longitud_incidencia' => -80.9520,
                'estado_incidencia' => 'EN_PROCESO',
                'prioridad_incidencia' => 'MEDIA',
                'id_ciudad' => $salinas->id_ciudad,
                'id_subtipo_incidencia' => $basura->id_subtipo_incidencia,
                'id_usuario' => $admin->id,
            ],
            [
                'nombre_incidencia' => 'Escombros en vereda de barrio Paraíso',
                'descripcion_incidencia' => 'Material de construcción abandonado obstruye el paso peatonal.',
                'latitud_incidencia' => -2.2310,
                'longitud_incidencia' => -80.9080,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'BAJA',
                'id_ciudad' => $laLibertad->id_ciudad,
                'id_subtipo_incidencia' => $escombros->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Árbol a punto de caer en vía a Montañita',
                'descripcion_incidencia' => 'Árbol grande inclinado sobre la carretera, peligro para vehículos.',
                'latitud_incidencia' => -1.8700,
                'longitud_incidencia' => -80.7500,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'ALTA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $arbol->id_subtipo_incidencia,
                'id_usuario' => $tecnico->id,
            ],
            [
                'nombre_incidencia' => 'Juegos infantiles rotos en parque de La Libertad',
                'descripcion_incidencia' => 'El columpio y el sube-y-baja están rotos, hay riesgo para los niños.',
                'latitud_incidencia' => -2.2320,
                'longitud_incidencia' => -80.9110,
                'estado_incidencia' => 'RESUELTO',
                'prioridad_incidencia' => 'MEDIA',
                'id_ciudad' => $laLibertad->id_ciudad,
                'id_subtipo_incidencia' => $juegos->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Vehículo abandonado en calle Guayaquil',
                'descripcion_incidencia' => 'Carro sin placas estacionado hace más de un mes, posible chatarra.',
                'latitud_incidencia' => -2.1894,
                'longitud_incidencia' => -79.8891,
                'estado_incidencia' => 'EN_PROCESO',
                'prioridad_incidencia' => 'BAJA',
                'id_ciudad' => $guayaquil->id_ciudad,
                'id_subtipo_incidencia' => $vehiculo->id_subtipo_incidencia,
                'id_usuario' => $admin->id,
            ],
            [
                'nombre_incidencia' => 'Ruido excesivo de taller mecánico',
                'descripcion_incidencia' => 'Taller trabaja hasta las 11pm con ruido de soldadura y martilleo.',
                'latitud_incidencia' => -2.2260,
                'longitud_incidencia' => -80.8600,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'BAJA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $ruido->id_subtipo_incidencia,
                'id_usuario' => $normal->id,
            ],
            [
                'nombre_incidencia' => 'Acera destruida frente a UPSE',
                'descripcion_incidencia' => 'La acera tiene huecos y raíces levantadas, varios estudiantes se han tropezado.',
                'latitud_incidencia' => -2.2285,
                'longitud_incidencia' => -80.8575,
                'estado_incidencia' => 'PENDIENTE',
                'prioridad_incidencia' => 'MEDIA',
                'id_ciudad' => $santaElena->id_ciudad,
                'id_subtipo_incidencia' => $aceras->id_subtipo_incidencia,
                'id_usuario' => $tecnico->id,
            ],
        ];

        foreach ($datos as $dato) {
            Incidencia::create($dato);
        }
    }
}
