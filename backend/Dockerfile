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
