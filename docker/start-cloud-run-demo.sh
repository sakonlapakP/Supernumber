#!/usr/bin/env sh
set -eu

export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/tmp/database.sqlite}"
export SESSION_DRIVER="${SESSION_DRIVER:-database}"
export CACHE_STORE="${CACHE_STORE:-database}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"
export LINE_CHANNEL_ACCESS_TOKEN="${LINE_CHANNEL_ACCESS_TOKEN:-}"
export LINE_CHANNEL_SECRET="${LINE_CHANNEL_SECRET:-}"
export LINE_GROUP_ID="${LINE_GROUP_ID:-}"
export LINE_ESTIMATE_GROUP_ID="${LINE_ESTIMATE_GROUP_ID:-}"
export LINE_ORDER_GROUP_ID="${LINE_ORDER_GROUP_ID:-}"
export LINE_ORDER_STATUS_GROUP_ID="${LINE_ORDER_STATUS_GROUP_ID:-}"
export LINE_TEST_GROUP_ID="${LINE_TEST_GROUP_ID:-}"
export LINE_ORDER_STATUS_EVENTS="${LINE_ORDER_STATUS_EVENTS:-submitted,paid,completed}"
export LINE_RETRY_TIMES="${LINE_RETRY_TIMES:-3}"
export LINE_RETRY_SLEEP_MS="${LINE_RETRY_SLEEP_MS:-1000}"

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

bootstrap_demo=0

if [ "${DB_CONNECTION}" = "sqlite" ]; then
  mkdir -p "$(dirname "${DB_DATABASE}")"

  if [ ! -f "${DB_DATABASE}" ]; then
    touch "${DB_DATABASE}"
    bootstrap_demo=1
  elif [ ! -s "${DB_DATABASE}" ]; then
    bootstrap_demo=1
  fi
fi

if [ "${bootstrap_demo}" -eq 1 ]; then
  php artisan migrate:fresh --force
  php artisan db:seed --class='Database\Seeders\CloudRunDemoSeeder' --force
else
  php artisan migrate --force
fi

if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
