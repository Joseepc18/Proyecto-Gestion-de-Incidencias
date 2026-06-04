<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Rol;


class RolesYUsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Rol::firstOrCreate(['nombre_rol' => 'admin']);
        $tecnico = Rol::firstOrCreate(['nombre_rol' => 'tecnico']);
        $normal = Rol::firstOrCreate(['nombre_rol' => 'normal']);

        User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
            'name' => 'Administrador',
            'password' => Hash::make('password123'),
            'id_rol' => $admin->id_rol,
        ]
        );

        User::firstOrCreate(
            ['email' => 'tecnico@sistema.com'],
            [
            'name' => 'Tecnico',
            'password' => Hash::make('password123'),
            'id_rol' => $tecnico->id_rol,
        ]
        );

        User::firstOrCreate(
            ['email' => 'normal@sistema.com'],
            [
            'name' => 'Normal',
            'password' => Hash::make('password123'),
            'id_rol' => $normal->id_rol,
        ]
        );
    }
}
