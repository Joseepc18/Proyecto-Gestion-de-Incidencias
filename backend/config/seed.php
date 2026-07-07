<?php

// Claves de las cuentas privilegiadas sembradas (super_admin/admin). Vía config() y no env()
// directo en el seeder: con el config cacheado en prod (artisan optimize), env() fuera de un
// archivo config/ devuelve null aunque la variable exista en el .env.
return [
    'superadmin_password' => env('SEED_SUPERADMIN_PASSWORD'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
];
