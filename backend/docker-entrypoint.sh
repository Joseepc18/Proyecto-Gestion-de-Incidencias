#!/bin/sh
set -e

# Asegura que el usuario de PHP-FPM (www-data) pueda escribir en storage y en la cache.
# Corre como root al arrancar el contenedor, antes de que FPM baje los workers a www-data.
# Necesario porque storage está bind-mounted desde el host: un chown en el Dockerfile no persiste
# (el bind-mount pisa los permisos en runtime). Esto arregla el fallo silencioso de store() al subir fotos.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R u+rwX,g+rwX storage bootstrap/cache 2>/dev/null || true

exec docker-php-entrypoint "$@"
