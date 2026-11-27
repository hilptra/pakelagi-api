#!/usr/bin/env bash
set -euo pipefail

export PORT="${PORT:-10000}"

# Render Nginx config with dynamic PORT
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure runtime writable directories exist with proper ownership
mkdir -p /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Optional opt-in database migrations on container startup
if [ "${RUN_MIGRATIONS_ON_START:-false}" = "true" ]; then
    echo "[entrypoint] RUN_MIGRATIONS_ON_START=true: running php artisan migrate --force..."
    if ! php /var/www/html/artisan migrate --force --no-interaction; then
        echo "[entrypoint] ERROR: Database migration failed. Aborting container startup." >&2
        exit 1
    fi
fi

# Optional opt-in database seeding on container startup (uses ADMIN_EMAIL & ADMIN_PASSWORD from env)
if [ "${RUN_SEED_ON_START:-false}" = "true" ]; then
    echo "[entrypoint] RUN_SEED_ON_START=true: running php artisan db:seed --force..."
    if ! php /var/www/html/artisan db:seed --force --no-interaction; then
        echo "[entrypoint] ERROR: Database seeding failed. Aborting container startup." >&2
        exit 1
    fi
fi

# Cache Laravel configuration, routes, and events at container startup using runtime env
echo "[entrypoint] Caching Laravel configuration, routes, and events..."
php /var/www/html/artisan config:cache --no-interaction
php /var/www/html/artisan route:cache --no-interaction
php /var/www/html/artisan event:cache --no-interaction
chown -R www-data:www-data /var/www/html/bootstrap/cache

exec "$@"
