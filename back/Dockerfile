# ====================================================================
# Backend Aula Virtual UNITEPC
# 1 sola imagen: PHP 8.2-FPM + Nginx + Supervisor (Laravel 12)
# ====================================================================
FROM php:8.2-fpm-alpine

# Dependencias del sistema + extensiones PHP
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    mysql-client \
    bash \
    && docker-php-ext-install pdo pdo_mysql gd zip bcmath opcache intl pcntl

# MinIO Client (mc) para crear el bucket en el entrypoint
RUN curl -fsSL https://dl.min.io/client/mc/release/linux-amd64/mc -o /usr/local/bin/mc \
    && chmod +x /usr/local/bin/mc

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuracion PHP, Nginx y Supervisor
COPY docker/php/php.ini /usr/local/etc/php/conf.d/aula.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/log/supervisor /run/nginx

# Working dir
WORKDIR /var/www

# Copiar codigo (en dev el volumen ./back sobreescribe esto en runtime)
COPY . .

# Permisos Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint (normalizar line endings por si se creo en Windows)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
