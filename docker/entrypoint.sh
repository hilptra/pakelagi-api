#!/usr/bin/env bash
set -euo pipefail

export PORT="${PORT:-10000}"

# Render Nginx config with dynamic PORT from Render
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure runtime writable directories exist with proper ownership
mkdir -p /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Note: Migrations and artisan optimize/cache are intentionally NOT auto-run at startup.
exec "$@"
