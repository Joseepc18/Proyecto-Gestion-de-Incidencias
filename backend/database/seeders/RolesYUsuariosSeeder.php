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
        // Los roles son datos de referencia (siempre se siembran).
        $superAdmin = Rol::firstOrCreate(['nombre_rol' => 'super_admin']);
        $admin = Rol::firstOrCreate(['nombre_rol' => 'admin']);
        Rol::firstOrCreate(['nombre_rol' => 'tecnico']);
        Rol::firstOrCreate(['nombre_rol' => 'normal']);

        // Cuenta super_admin: gestiona usuarios, catálogos y permisos (el superset).
        User::firstOrCreate(
            ['email' => 'superadmin@sistema.com'],
            [
                'name' => 'Super Administrador',
                // En prod la clave real vive en SEED_SUPERADMIN_PASSWORD del .env; el fallback es solo para dev.
                'password' => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'password123')),
                'id_rol' => $superAdmin->id_rol,
            ]
        );

        // Cuenta admin operativa. Los técnicos se crean desde la gestión de usuarios
        // (super_admin) y los usuarios normales por registro público.
        User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name' => 'Administrador',
                // En prod la clave real vive en SEED_ADMIN_PASSWORD del .env; el fallback es solo para dev.
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password123')),
                'id_rol' => $admin->id_rol,
            ]
        );
    }
}
