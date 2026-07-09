#!/bin/sh
set -e

# Asegura que el usuario de PHP-FPM (www-data) pueda escribir en storage y en la cache.
# Corre como root al arrancar el contenedor, antes de que FPM baje los workers a www-data.
# Necesario porque storage está bind-mounted desde el host: un chown en el Dockerfile no persiste
# (el bind-mount pisa los permisos en runtime). Esto arregla el fallo silencioso de store() al subir fotos.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R u+rwX,g+rwX storage bootstrap/cache 2>/dev/null || true

# Rehace la cache de config/rutas/eventos/vistas en cada arranque del contenedor, solo en producción.
# bootstrap/cache está bind-mounted, así que un "docker compose restart" NO reconstruye la imagen
# y conserva la cache vieja; sin este paso, un deploy que solo reinicia serviría rutas/config obsoletas.
# Fuera de producción NO se cachea: config:cache congela el .env leído en ese momento, y pisaría
# el .env.testing que usa "php artisan test" (rompe la BD/rate-limits de la suite).
if [ "$(grep -m1 '^APP_ENV=' .env 2>/dev/null | cut -d '=' -f2)" = "production" ]; then
    php artisan optimize:clear
    php artisan optimize
fi

exec docker-php-entrypoint "$@"
