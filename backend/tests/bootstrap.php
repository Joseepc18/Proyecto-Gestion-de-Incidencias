<?php

// Salvaguarda: la suite borra y rehace la base entera, así que nunca debe correr con la config cacheada.
// config:cache congela el .env leído en ese momento y anula los <env> de phpunit.xml (BD, caché, cola,
// correo), por lo que los tests apuntarían a la base, el Redis y el mailer reales. Pasa en el servidor
// de producción, donde docker-entrypoint.sh corre "artisan optimize" en cada arranque.
if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
    fwrite(STDERR, PHP_EOL.
        '  La configuración está cacheada (bootstrap/cache/config.php).'.PHP_EOL.
        '  Los <env> de phpunit.xml no aplican y la suite correría contra la BD real.'.PHP_EOL.
        '  En local: php artisan config:clear. En el servidor de producción: no se corren los tests.'.PHP_EOL.PHP_EOL);

    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
