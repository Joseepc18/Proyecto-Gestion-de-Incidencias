<?php

namespace Database\Seeders;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Enums\RolAsignacion;
use App\Models\Ciudad;
use App\Models\Comentario;
use App\Models\Evidencia;
use App\Models\Incidencia;
use App\Models\Rol;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Seeder de demo para la defensa: usuarios de ejemplo (los 4 roles) + incidencias realistas en
// varios estados. Reutiliza el pipeline real (SPs asignar_tecnico/resolver_incidencia y los
// triggers de historial/fecha_resolucion) en vez de insertar historial a mano, para que la data
// de demo quede idéntica a la que produce la app en uso normal.
// Standalone: php artisan db:seed --class=DemoSeeder (siembra también roles/permisos/catálogos si faltan).
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesYUsuariosSeeder::class,
            PermisosSeeder::class,
            UbicacionSeeder::class,
            TipoYSubtipoSeeder::class,
        ]);

        $admin = User::where('email', 'admin@sistema.com')->firstOrFail();
        $u = $this->crearUsuariosDemo();

        $definiciones = [
            [
                'titulo' => 'Bache profundo en la vía a Ballenita',
                'descripcion' => 'Hueco de más de medio metro en la vía a Ballenita, varios carros ya se han pinchado.',
                'direccion' => 'Vía a Ballenita, km 3',
                'lat' => -2.2050, 'lng' => -80.8500,
                'ciudad' => 'Santa Elena', 'subtipo' => 'Baches',
                'reportero' => $u['ciudadano1'],
                'fotos_reporte' => 0,
                'flujo' => 'pendiente',
            ],
            [
                'titulo' => 'Luminarias apagadas en el malecón de Salinas',
                'descripcion' => 'Tres postes seguidos sin luz en el malecón, de noche queda muy oscuro y es peligroso.',
                'direccion' => 'Malecón de Salinas, altura del muelle',
                'lat' => -2.2120, 'lng' => -80.9551,
                'ciudad' => 'Salinas', 'subtipo' => 'Luminaria apagada',
                'reportero' => $u['ciudadano2'],
                'fotos_reporte' => 2,
                'flujo' => 'pendiente',
            ],
            [
                'titulo' => 'Contenedor de basura desbordado en barrio Simón Bolívar',
                'descripcion' => 'El contenedor lleva días desbordado, hay bolsas de basura regadas por la acera.',
                'direccion' => 'Barrio Simón Bolívar, calle principal',
                'lat' => -2.2280, 'lng' => -80.8600,
                'ciudad' => 'Santa Elena', 'subtipo' => 'Contenedor desbordado',
                'reportero' => $u['ciudadano3'],
                'fotos_reporte' => 1,
                'flujo' => 'pendiente',
            ],
            [
                'titulo' => 'Semáforo apagado en cruce escolar de la Cdla. Universitaria',
                'descripcion' => 'El semáforo frente a la UPSE lleva tres días apagado, a la salida de clases es un caos.',
                'direccion' => 'Cdla. Universitaria, frente a la UPSE',
                'lat' => -2.2320, 'lng' => -80.9110,
                'ciudad' => 'La Libertad', 'subtipo' => 'Semáforo apagado',
                'reportero' => $u['ciudadano1'],
                'fotos_reporte' => 2,
                'flujo' => 'en_proceso',
                'prioridad' => PrioridadIncidencia::Alta,
                'responsable' => $u['tecnico1'],
                'comentarios' => [
                    [$u['ciudadano1'], '¿Ya tienen fecha para arreglarlo? Es zona escolar.'],
                    [$admin, 'Ya asignamos un técnico, debería quedar resuelto esta semana.'],
                ],
            ],
            [
                'titulo' => 'Fuga de agua potable en Av. Primero de Mayo',
                'descripcion' => 'Tubería reventada, el agua lleva dos días corriendo por la calle.',
                'direccion' => 'Av. Primero de Mayo y Guayaquil',
                'lat' => -2.2300, 'lng' => -80.9080,
                'ciudad' => 'La Libertad', 'subtipo' => 'Fuga de agua potable',
                'reportero' => $u['ciudadano2'],
                'fotos_reporte' => 1,
                'flujo' => 'en_proceso',
                'prioridad' => PrioridadIncidencia::Alta,
                'responsable' => $u['tecnico1'],
                'apoyo' => $u['tecnico2'],
                'comentarios' => [
                    [$u['ciudadano2'], 'Sigue saliendo agua, ojalá vengan pronto.'],
                ],
            ],
            [
                'titulo' => 'Árbol inclinado con riesgo de caer en la vía a Montañita',
                'descripcion' => 'Un árbol grande quedó inclinado sobre la vía tras la última tormenta, puede caer en cualquier momento.',
                'direccion' => 'Vía a Montañita, km 5',
                'lat' => -1.8700, 'lng' => -80.7500,
                'ciudad' => 'Santa Elena', 'subtipo' => 'Árbol o rama grande',
                'reportero' => $u['ciudadano3'],
                'fotos_reporte' => 1,
                'flujo' => 'en_proceso_sin_tecnico',
                'prioridad' => PrioridadIncidencia::Media,
            ],
            [
                'titulo' => 'Bache reparado en la calle 9 de Octubre',
                'descripcion' => 'Bache grande frente al mercado municipal que afectaba el tránsito.',
                'direccion' => 'Calle 9 de Octubre y Guayaquil',
                'lat' => -2.2270, 'lng' => -80.8595,
                'ciudad' => 'Santa Elena', 'subtipo' => 'Baches',
                'reportero' => $u['ciudadano1'],
                'fotos_reporte' => 2,
                'fotos_resolucion' => 1,
                'flujo' => 'resuelta',
                'prioridad' => PrioridadIncidencia::Alta,
                'responsable' => $u['tecnico1'],
            ],
            [
                'titulo' => 'Escombros retirados de la vereda en La Libertad',
                'descripcion' => 'Restos de construcción abandonados que obstruían el paso peatonal.',
                'direccion' => 'Cdla. Universitaria, calle 5',
                'lat' => -2.2325, 'lng' => -80.9115,
                'ciudad' => 'La Libertad', 'subtipo' => 'Escombros',
                'reportero' => $u['ciudadano2'],
                'fotos_reporte' => 1,
                'fotos_resolucion' => 2,
                'flujo' => 'resuelta',
                'prioridad' => PrioridadIncidencia::Media,
                'responsable' => $u['tecnico2'],
            ],
            [
                'titulo' => 'Fuga de agua reaparece en el malecón de Salinas',
                'descripcion' => 'Ya se había reparado, pero volvió a salir agua en el mismo punto.',
                'direccion' => 'Malecón de Salinas, Barrio Capulí',
                'lat' => -2.2130, 'lng' => -80.9560,
                'ciudad' => 'Salinas', 'subtipo' => 'Fuga de agua potable',
                'reportero' => $u['ciudadano3'],
                'fotos_reporte' => 1,
                'fotos_resolucion' => 1,
                'flujo' => 'resuelta_reapertura',
                'prioridad' => PrioridadIncidencia::Alta,
                'responsable' => $u['tecnico1'],
            ],
            [
                'titulo' => 'Vehículo abandonado retirado en Santa Elena',
                'descripcion' => 'Carro sin placas estacionado hace semanas frente a la plaza central.',
                'direccion' => 'Av. Eleodoro Solórzano',
                'lat' => -2.2260, 'lng' => -80.8580,
                'ciudad' => 'Santa Elena', 'subtipo' => 'Vehículo abandonado',
                'reportero' => $u['ciudadano1'],
                'fotos_reporte' => 1,
                'fotos_resolucion' => 1,
                'flujo' => 'archivada',
                'prioridad' => PrioridadIncidencia::Baja,
                'responsable' => $u['tecnico2'],
            ],
            [
                'titulo' => 'Juegos infantiles reparados en parque de La Libertad',
                'descripcion' => 'El columpio y el sube-y-baja estaban rotos, ya se completó el arreglo.',
                'direccion' => 'Parque central de La Libertad',
                'lat' => -2.2310, 'lng' => -80.9095,
                'ciudad' => 'La Libertad', 'subtipo' => 'Juegos infantiles',
                'reportero' => $u['ciudadano2'],
                'fotos_reporte' => 2,
                'fotos_resolucion' => 1,
                'flujo' => 'archivada',
                'prioridad' => PrioridadIncidencia::Media,
                'responsable' => $u['tecnico1'],
            ],
            [
                'titulo' => 'Perro herido en la vía cerca del terminal de Salinas',
                'descripcion' => 'Un perro callejero fue atropellado y sigue tirado junto a la vía.',
                'direccion' => 'Vía al terminal terrestre',
                'lat' => -2.2080, 'lng' => -80.9500,
                'ciudad' => 'Salinas', 'subtipo' => 'Animales callejeros',
                'reportero' => $u['ciudadano3'],
                'fotos_reporte' => 0,
                'flujo' => 'pendiente',
            ],
            [
                'titulo' => 'Cable colgando muy bajo en calle Guayaquil',
                'descripcion' => 'No estoy seguro si es de electricidad o de internet, pero está colgando muy bajo.',
                'direccion' => 'Calle Guayaquil, cerca del mercado',
                'lat' => -2.2275, 'lng' => -80.8590,
                'ciudad' => 'Santa Elena', 'subtipo' => 'No estoy seguro',
                'reportero' => $u['ciudadano1'],
                'fotos_reporte' => 1,
                'flujo' => 'pendiente',
            ],
            [
                'titulo' => 'Contenedor volcado en Av. Cuarta de Salinas',
                'descripcion' => 'El contenedor está volcado desde ayer y la basura se está regando con el viento.',
                'direccion' => 'Av. Cuarta, Barrio Capulí',
                'lat' => -2.2090, 'lng' => -80.9510,
                'ciudad' => 'Salinas', 'subtipo' => 'Contenedor público',
                'reportero' => $u['ciudadano2'],
                'fotos_reporte' => 2,
                'flujo' => 'en_proceso',
                'prioridad' => PrioridadIncidencia::Alta,
                'responsable' => $u['tecnico2'],
                'comentarios' => [
                    [$admin, 'Ya se asignó un técnico para retirar el contenedor dañado.'],
                ],
            ],
        ];

        foreach ($definiciones as $d) {
            $this->crearIncidenciaDemo($admin, $d);
        }
    }

    private function crearUsuariosDemo(): array
    {
        $password = Hash::make(config('seed.demo_password') ?: 'Demo12345');

        $definiciones = [
            'ciudadano1' => ['María José Tigrero', 'ciudadano1@sistema.com', Rol::NORMAL],
            'ciudadano2' => ['Carlos Suárez', 'ciudadano2@sistema.com', Rol::NORMAL],
            'ciudadano3' => ['Lucía Borbor', 'ciudadano3@sistema.com', Rol::NORMAL],
            'tecnico1' => ['Pedro Reyes', 'tecnico1@sistema.com', Rol::TECNICO],
            'tecnico2' => ['Ana Villón', 'tecnico2@sistema.com', Rol::TECNICO],
        ];

        $usuarios = [];
        foreach ($definiciones as $clave => [$nombre, $email, $rol]) {
            $usuarios[$clave] = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'id_rol' => Rol::where('nombre_rol', $rol)->value('id_rol'),
                ]
            );
        }

        return $usuarios;
    }

    // Crea la incidencia (idempotente por título) y, según 'flujo', la hace avanzar por el pipeline
    // real: reclamo + prioridad + cambiarEstado (dispara el trigger de historial), asignar_tecnico
    // (SP) y resolver_incidencia (SP, dispara fecha_resolucion + historial).
    private function crearIncidenciaDemo(User $admin, array $d): void
    {
        $incidencia = Incidencia::firstOrCreate(
            ['nombre_incidencia' => $d['titulo']],
            [
                'descripcion_incidencia' => $d['descripcion'],
                'direccion_incidencia' => $d['direccion'],
                'latitud_incidencia' => $d['lat'],
                'longitud_incidencia' => $d['lng'],
                'id_ciudad' => $this->ciudad($d['ciudad'])->id_ciudad,
                'id_subtipo_incidencia' => $this->subtipo($d['subtipo'])->id_subtipo_incidencia,
                'id_usuario' => $d['reportero']->id,
            ]
        );

        // Ya sembrada en una corrida anterior: no duplicar evidencias/asignaciones/historial.
        if (! $incidencia->wasRecentlyCreated) {
            return;
        }

        for ($i = 1; $i <= ($d['fotos_reporte'] ?? 0); $i++) {
            Evidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_usuario' => $d['reportero']->id,
                'url_evidencia' => $this->fotoDemo($d['titulo']." (reporte $i)"),
                'tipo_evidencia' => 'REPORTE',
            ]);
        }

        if ($d['flujo'] === 'pendiente') {
            return;
        }

        $incidencia->update([
            'id_admin_atiende' => $admin->id,
            'reclamo_visto_en' => now(),
            'prioridad_incidencia' => $d['prioridad']->value,
        ]);
        $this->transicionarEstado($incidencia, EstadoIncidencia::EnProceso, $admin);

        if (! empty($d['responsable'])) {
            $this->asignarTecnico($incidencia, $d['responsable'], RolAsignacion::Responsable);
        }
        if (! empty($d['apoyo'])) {
            $this->asignarTecnico($incidencia, $d['apoyo'], RolAsignacion::Apoyo);
        }

        if ($incidencia->fresh()->estaListaParaCorreoDetalle()) {
            $incidencia->update(['correo_detalle_enviado' => true]);
        }

        foreach ($d['comentarios'] ?? [] as [$autor, $texto]) {
            Comentario::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_usuario' => $autor->id,
                'comentario' => $texto,
            ]);
        }

        if (in_array($d['flujo'], ['en_proceso', 'en_proceso_sin_tecnico'], true)) {
            return;
        }

        $this->resolverIncidencia($incidencia, $d['responsable']);
        $incidencia->refresh();

        for ($i = 1; $i <= ($d['fotos_resolucion'] ?? 0); $i++) {
            Evidencia::create([
                'id_incidencia' => $incidencia->id_incidencia,
                'id_usuario' => $d['responsable']->id,
                'url_evidencia' => $this->fotoDemo($d['titulo']." (resolución $i)"),
                'tipo_evidencia' => 'RESOLUCION',
            ]);
        }

        if ($d['flujo'] === 'resuelta_reapertura') {
            $incidencia->update(['reapertura_solicitada' => true]);
        }

        if ($d['flujo'] === 'archivada') {
            $this->transicionarEstado($incidencia, EstadoIncidencia::Cerrado, $admin);
        }
    }

    // Mismo patrón que el controller: set_config('app.actor_id') y el UPDATE en una sola
    // transacción, para que el trigger de historial atribuya el cambio al actor correcto.
    private function transicionarEstado(Incidencia $incidencia, EstadoIncidencia $nuevo, User $actor): void
    {
        DB::transaction(function () use ($incidencia, $nuevo, $actor) {
            DB::statement("SELECT set_config('app.actor_id', ?, true)", [(string) $actor->id]);
            $incidencia->update(['estado_incidencia' => $nuevo->value]);
        });
    }

    private function asignarTecnico(Incidencia $incidencia, User $tecnico, RolAsignacion $rol): void
    {
        DB::statement('CALL asignar_tecnico(?, ?, ?)', [$incidencia->id_incidencia, $tecnico->id, $rol->value]);
    }

    private function resolverIncidencia(Incidencia $incidencia, User $tecnico): void
    {
        DB::statement('CALL resolver_incidencia(?, ?)', [$incidencia->id_incidencia, $tecnico->id]);
    }

    private function ciudad(string $nombre): Ciudad
    {
        return Ciudad::where('nombre_ciudad', $nombre)->firstOrFail();
    }

    private function subtipo(string $prefijo): SubtipoIncidencia
    {
        return SubtipoIncidencia::where('nombre_subtipo_incidencia', 'like', $prefijo.'%')->firstOrFail();
    }

    // Genera una foto placeholder (JPG con etiqueta) para que las evidencias de demo sean archivos
    // reales y visibles en el lightbox, sin depender de fotos externas.
    private function fotoDemo(string $etiqueta): string
    {
        $imagen = imagecreatetruecolor(640, 480);
        $fondo = imagecolorallocate($imagen, random_int(50, 170), random_int(50, 170), random_int(50, 170));
        imagefill($imagen, 0, 0, $fondo);
        $blanco = imagecolorallocate($imagen, 255, 255, 255);
        imagestring($imagen, 3, 15, 15, 'FOTO DE DEMOSTRACION', $blanco);
        foreach (explode("\n", wordwrap($etiqueta, 45)) as $i => $linea) {
            imagestring($imagen, 4, 15, 40 + $i * 18, $linea, $blanco);
        }

        ob_start();
        imagejpeg($imagen, quality: 75);
        $binario = ob_get_clean();
        imagedestroy($imagen);

        $ruta = 'incidencias/demo-'.Str::uuid()->toString().'.jpg';
        Storage::disk('evidencias')->put($ruta, $binario);

        return $ruta;
    }
}
