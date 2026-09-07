# =============================================================================
# DBRRScita — Despliegue en Render.com (PHP 8.3 + Apache)
# =============================================================================
# Requisitos del proyecto (composer.lock):
#   - PHP: rubix/ml >=7.4, phpmailer >=5.5, amphp >=7.1  => 8.3 cumple.
#   - ext-json / ctype / filter / hash son built-in en la imagen oficial.
#   - El código usa PDO MySQL, cURL (Qdrant Cloud) y mb_* (nativo).
# =============================================================================

FROM php:8.3-apache

# Composer instalado desde la imagen oficial de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Extensiones PHP necesarias:
#   pdo_mysql + mysqli -> conexión a MySQL (Aiven)
#   curl               -> API de Qdrant Cloud
#   mbstring           -> funciones mb_* usadas en el código
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install pdo_mysql mysqli curl mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Instalación de dependencias con Composer (vendor/ NO se copia: está en .dockerignore)
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction \
    && composer clear-cache

# Código de la aplicación
COPY . /var/www/html

# Apache sirve desde Publico/ (DocumentRoot + AllowOverride All).
# El entrypoint ajusta Listen/ports para que Apache escuche en $PORT (Render).
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV PORT=80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]