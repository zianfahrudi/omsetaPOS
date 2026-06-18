#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Tunggu MySQL siap (maks ~60 detik).
if [[ -n "${DB_HOST:-}" ]]; then
    echo "==> Menunggu database ${DB_HOST}:${DB_PORT:-3306} ..."
    for i in $(seq 1 30); do
        if mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" --silent 2>/dev/null; then
            echo "==> Database siap."
            break
        fi
        sleep 2
    done
fi

# Inisialisasi hanya untuk container web (CMD = supervisord).
# Container queue/scheduler (punya argumen lain) langsung exec tanpa migrasi.
if [[ "${1:-}" == "supervisord" ]]; then
    # APP_KEY wajib ada. Generate bila kosong (sebaiknya diset via .env).
    if [[ -z "${APP_KEY:-}" ]]; then
        php artisan key:generate --force || true
    fi

    php artisan migrate --force || true
    php artisan storage:link || true

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache || true

    chown -R www-data:www-data storage bootstrap/cache || true
fi

exec "$@"
