<?php

// Claves de las cuentas privilegiadas sembradas (super_admin/admin). Vía config() y no env()
// directo en el seeder: con el config cacheado en prod (artisan optimize), env() fuera de un
// archivo config/ devuelve null aunque la variable exista en el .env.
return [
    // Correo de la cuenta super_admin sembrada; en producción es una persona real, por eso no
    // se escribe literal en el repositorio, viene del .env (que no viaja por git).
    'superadmin_email' => env('SEED_SUPERADMIN_EMAIL'),
    'superadmin_password' => env('SEED_SUPERADMIN_PASSWORD'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    // Cuentas de ejemplo del DemoSeeder (ciudadanos/técnicos); no es obligatoria, cae al fallback.
    'demo_password' => env('SEED_DEMO_PASSWORD'),
];
