#!/bin/sh
set -eu

cd /app

mkdir -p \
  storage/app/public \
  storage/app/private \
  storage/app/tmp \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Coolify mounts /app/storage and may run the container as a non-root uid.
# Make sure the runtime user can write compiled views, sessions, and logs.
chmod -R a+rwX storage bootstrap/cache || true

php artisan storage:link --force >/dev/null 2>&1 || true

if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache --no-interaction
  php artisan route:cache --no-interaction
  php artisan view:cache --no-interaction
fi

# Re-apply after artisan (caches create root-owned files).
chmod -R a+rwX storage bootstrap/cache || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec frankenphp run --config /etc/caddy/Caddyfile
