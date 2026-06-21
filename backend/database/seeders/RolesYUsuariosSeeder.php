<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesYUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        // Los 3 roles son datos de referencia (siempre se siembran).
        $admin = Rol::firstOrCreate(['nombre_rol' => 'admin']);
        Rol::firstOrCreate(['nombre_rol' => 'tecnico']);
        Rol::firstOrCreate(['nombre_rol' => 'normal']);

        // Solo se siembra la cuenta admin. Los técnicos se crearán desde la
        // gestión de usuarios (admin) y los usuarios normales por registro público.
        User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password123'),
                'id_rol' => $admin->id_rol,
            ]
        );
    }
}
