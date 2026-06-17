#!/usr/bin/env bash
#
# Skrip deploy omsetaPOS ke VPS (produksi).
# Pakai: ./deploy.sh            -> deploy update biasa (aman, tanpa hapus data)
#        ./deploy.sh --seed     -> sekaligus jalankan seeder (mis. setup awal)
#
# Aman: TIDAK pernah migrate:fresh / drop tabel. Migrasi pakai --force.
set -euo pipefail

cd "$(dirname "$0")"

SEED=false
if [[ "${1:-}" == "--seed" ]]; then
    SEED=true
fi

echo "==> Mode maintenance ON"
php artisan down --render="errors::503" || true
trap 'php artisan up || true' EXIT

echo "==> Tarik kode terbaru"
git pull --ff-only

echo "==> Composer (produksi)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Build aset frontend"
npm ci
npm run build

echo "==> Migrasi database (--force, tanpa hapus data)"
php artisan migrate --force

if [[ "$SEED" == "true" ]]; then
    echo "==> Seeder"
    php artisan db:seed --force
fi

echo "==> Cache ulang konfigurasi"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

echo "==> Symlink storage"
php artisan storage:link || true

echo "==> Mode maintenance OFF"
php artisan up
trap - EXIT

echo "==> Selesai. Deploy sukses."
