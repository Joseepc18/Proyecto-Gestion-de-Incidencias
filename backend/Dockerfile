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
    && docker-php-ext-install pdo pdo_pgsql gd \
    && pecl install redis \
    && docker-php-ext-enable redis

# Traer Composer (necesario para instalar Laravel)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir la carpeta de trabajo dentro del contenedor
WORKDIR /var/www/html
