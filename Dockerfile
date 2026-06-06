# syntax=docker/dockerfile:1
# AgriCloud backend (Laravel 12) — image produksi untuk Render.
# nginx + php-fpm dalam satu container, listen di $PORT yang di-inject Render.

FROM php:8.2-fpm-alpine

# --- Dependensi sistem & ekstensi PHP ---------------------------------------
RUN apk add --no-cache \
        nginx \
        gettext \
        postgresql-dev \
        libpq \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        pcntl \
        opcache \
    && apk del $PHPIZE_DEPS

# --- Composer ----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependency dulu (manfaatkan layer cache) lalu copy sisa source.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Konfigurasi runtime -----------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-agricloud.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render meng-inject $PORT (default 10000). EXPOSE hanya dokumentasi.
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
