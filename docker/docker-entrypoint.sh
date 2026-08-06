#!/bin/sh
set -e

cd /var/www/html

# Ensure framework dirs exist and are writable
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Public storage symlink (uploads live on the Render disk at storage/app/public)
if [ ! -e public/storage ]; then
    ln -s /var/www/html/storage/app/public /var/www/html/public/storage
fi

# Render routes traffic to the PORT env var; bake it into nginx config
export PORT="${PORT:-80}"
envsubst '${PORT}' < docker/nginx.conf > /etc/nginx/sites-available/default

# Laravel needs the vendor package manifest
php artisan package:discover --ansi || true

# Apply migrations (no-op when already applied). Fail loudly on real errors.
php artisan migrate --force

# Production optimization: cached config/routes/views
php artisan optimize || true

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
