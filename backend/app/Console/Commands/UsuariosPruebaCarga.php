<?php

namespace App\Console\Commands;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UsuariosPruebaCarga extends Command
{
    protected $signature = 'carga:usuarios-prueba {accion : crear|borrar} {--cantidad=15} {--password=password123}';

    protected $description = 'Crea o borra ciudadanos de prueba (cargaN@test.local) para la prueba de capacidad autenticada';

    public function handle(): int
    {
        $accion = $this->argument('accion');
        $cantidad = (int) $this->option('cantidad');

        // Se manejan como correos reservados: nadie real usa @test.local, así el borrado es seguro.
        if ($accion === 'crear') {
            // Son cuentas ya verificadas y con una clave conocida: en producción serían puertas abiertas.
            // El borrado sí se permite en cualquier entorno, para poder limpiar si alguna llegó a colarse.
            if ($this->getLaravel()->environment('production')) {
                $this->error('Este comando no se puede correr en producción: crearía cuentas verificadas con una contraseña conocida.');

                return self::FAILURE;
            }

            $idRol = Rol::where('nombre_rol', 'normal')->value('id_rol');
            $hash = Hash::make($this->option('password'));

            for ($i = 1; $i <= $cantidad; $i++) {
                User::updateOrCreate(
                    ['email' => "carga{$i}@test.local"],
                    [
                        'name' => "Carga {$i}",
                        'password' => $hash,
                        'id_rol' => $idRol,
                        'email_verified_at' => now(),
                    ]
                );
            }

            $this->info("{$cantidad} ciudadano(s) de prueba listos (carga1..carga{$cantidad}@test.local).");

            return self::SUCCESS;
        }

        if ($accion === 'borrar') {
            // forceDelete: borrado físico, no soft delete, para no dejar rastro tras la prueba.
            $borrados = User::where('email', 'like', 'carga%@test.local')->forceDelete();

            $this->info("{$borrados} ciudadano(s) de prueba borrado(s).");

            return self::SUCCESS;
        }

        $this->error("Acción no válida: usa 'crear' o 'borrar'.");

        return self::FAILURE;
    }
}
