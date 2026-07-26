<?php

namespace App\Console\Commands;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Console\Command;

// Vía de "break-glass" para provisionar o recuperar un Administrador del Sistema (issue #19).
// La creación de super_admin sigue FUERA de la interfaz (CrearUsuarioRequest solo acepta tecnico|admin):
// esto no amplía la superficie de ataque porque quien puede ejecutar artisan ya tiene el servidor y la base de datos,
// o sea que ya podría hacer el mismo UPDATE a mano. Lo que aporta es un procedimiento repetible y auditable
// en lugar de tocar la BD de producción a pulso.
class PromoverSuperAdmin extends Command
{
    protected $signature = 'usuario:promover-superadmin {email : Correo de la cuenta que pasa a Administrador del Sistema}';

    protected $description = 'Asciende una cuenta existente al rol super_admin (Administrador del Sistema)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $usuario = User::where('email', $email)->first();
        if (! $usuario) {
            $this->error("No existe una cuenta activa con el correo {$email}.");

            return self::FAILURE;
        }

        if ($usuario->esSuperAdmin()) {
            $this->info("{$usuario->name} ya es Administrador del Sistema.");

            return self::SUCCESS;
        }

        $rol = Rol::where('nombre_rol', Rol::SUPER_ADMIN)->first();
        if (! $rol) {
            $this->error('No existe el rol super_admin: siembra los roles antes (php artisan db:seed).');

            return self::FAILURE;
        }

        $rolAnterior = $usuario->rol()->value('nombre_rol') ?? 'sin rol';
        $usuario->update(['id_rol' => $rol->id_rol]);

        $this->info("{$usuario->name} ({$email}) pasó de {$rolAnterior} a Administrador del Sistema.");
        // El rol lleva 2FA obligatorio: si la cuenta no lo tenía, el propio login la obliga a configurarlo.
        $this->line('Recuerda: en el próximo inicio de sesión se le exigirá el segundo factor.');

        return self::SUCCESS;
    }
}
