FROM php:8.3-fpm

# Instalar dependencias del sistema y extensiones para PostgreSQL, Redis y GD (imágenes)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Pool PHP-FPM más ancho y con arranque cálido: sobra RAM (~40 MiB de 768 MiB por réplica) y el server es CPU-bound (4 hilos), así que conviene absorber ráfagas con más workers listos en vez del pool por defecto (max_children=5)
RUN sed -i \
    -e 's/^pm.max_children = .*/pm.max_children = 8/' \
    -e 's/^pm.start_servers = .*/pm.start_servers = 4/' \
    -e 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 3/' \
    -e 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 6/' \
    -e 's/^;pm.max_requests = .*/pm.max_requests = 500/' \
    /usr/local/etc/php-fpm.d/www.conf

# Límites de subida de PHP (alineados con client_max_body_size 15M de Nginx)
RUN { \
    echo 'upload_max_filesize=15M'; \
    echo 'post_max_size=20M'; \
  } > /usr/local/etc/php/conf.d/uploads.ini

# Traer Composer (necesario para instalar Laravel)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir la carpeta de trabajo dentro del contenedor
WORKDIR /var/www/html

# Entrypoint propio: asegura permisos de storage para www-data antes de arrancar PHP-FPM
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
