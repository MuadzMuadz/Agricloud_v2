#!/bin/sh
set -e

# Render meng-inject $PORT; fallback 10000 untuk run lokal.
export PORT="${PORT:-10000}"

echo "[entrypoint] Render nginx listen di port ${PORT}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Cache konfigurasi & route untuk performa produksi.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi (idempotent, aman dijalankan tiap deploy).
php artisan migrate --force

# Pastikan symlink storage publik ada (abaikan bila sudah).
php artisan storage:link 2>/dev/null || true

# php-fpm di background, nginx di foreground (PID 1 container).
php-fpm -D
exec nginx -g 'daemon off;'
