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

php artisan storage:link --force >/dev/null 2>&1 || true

if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache --no-interaction
  php artisan route:cache --no-interaction
  php artisan view:cache --no-interaction
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

exec frankenphp run --config /etc/caddy/Caddyfile
