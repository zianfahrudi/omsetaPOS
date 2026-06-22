# syntax=docker/dockerfile:1

# ── Stage 1: build aset frontend (Vite) ───────────────────────────────
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY resources ./resources
COPY vite.config.* ./
COPY public ./public
RUN npm run build

# ── Stage 2: vendor PHP (composer, tanpa dev) ─────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Tunda script artisan sampai source lengkap (autoload-dump butuh artisan).
# Abaikan platform-req ekstensi PHP: image composer:2 minim ekstensi, tapi
# image runtime (php:8.3-fpm) sudah memasang intl/gd/zip/bcmath/dll.
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-reqs

# ── Stage 3: runtime (php-fpm + nginx + supervisor) ───────────────────
FROM php:8.3-fpm-alpine AS runtime

# Dependensi sistem + ekstensi PHP yang dibutuhkan Laravel/Filament.
RUN apk add --no-cache \
        nginx supervisor bash mysql-client \
        libpng libjpeg-turbo freetype libzip icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath gd zip intl exif pcntl opcache \
    && apk del .build-deps

WORKDIR /var/www/html

# Source aplikasi.
COPY . .

# Hasil build dari stage sebelumnya.
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Generate manifest paket & asset Filament (composer tak ada di runtime).
RUN php artisan package:discover --ansi || true \
    && php artisan filament:upgrade || true

# Konfigurasi nginx / php-fpm / supervisor / opcache.
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache \
    && mkdir -p /run/nginx

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
