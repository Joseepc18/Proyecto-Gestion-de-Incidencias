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

        // En producción las claves de las cuentas privilegiadas son obligatorias: nunca el fallback público de dev.
        // Vía config() y no env() directo: con el config cacheado en prod, env() fuera de config/ devuelve null.
        $superEmail = config('seed.superadmin_email');
        $superPassword = config('seed.superadmin_password');
        $adminPassword = config('seed.admin_password');
        if (app()->environment('production') && (empty($superEmail) || empty($superPassword) || empty($adminPassword))) {
            throw new \RuntimeException('En producción define SEED_SUPERADMIN_EMAIL, SEED_SUPERADMIN_PASSWORD y SEED_ADMIN_PASSWORD antes de sembrar.');
        }

        // Cuenta super_admin: gestiona usuarios, catálogos y permisos (el superset).
        // Nace verificada como el resto de vías de alta: sin email_verified_at el middleware 'verificado' le cerraría comentarios y evidencias.
        User::firstOrCreate(
            ['email' => $superEmail ?: 'superadmin@sistema.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make($superPassword ?: 'password123'),
                'id_rol' => $superAdmin->id_rol,
                'email_verified_at' => now(),
            ]
        );

        // Cuenta admin operativa. Los técnicos se crean desde la gestión de usuarios
        // (super_admin) y los usuarios normales por registro público.
        User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make($adminPassword ?: 'password123'),
                'id_rol' => $admin->id_rol,
                'email_verified_at' => now(),
            ]
        );
    }
}
