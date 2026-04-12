#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$(cd -- "${SCRIPT_DIR}/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-}"
QUEUE_NAME="${QUEUE_NAME:-notifications}"
WORKER_SLEEP="${WORKER_SLEEP:-3}"
WORKER_TRIES="${WORKER_TRIES:-3}"
WORKER_TIMEOUT="${WORKER_TIMEOUT:-120}"
WORKER_MAX_TIME="${WORKER_MAX_TIME:-3600}"
WORKER_MEMORY="${WORKER_MEMORY:-256}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "Required command not found: $PHP_BIN" >&2
  exit 1
fi

cd "$APP_DIR"

if [ ! -f artisan ]; then
  echo "Could not find artisan in ${APP_DIR}" >&2
  exit 1
fi

ARGS=("artisan" "queue:work")

if [ -n "${QUEUE_CONNECTION}" ]; then
  ARGS+=("${QUEUE_CONNECTION}")
fi

ARGS+=(
  "--queue=${QUEUE_NAME}"
  "--sleep=${WORKER_SLEEP}"
  "--tries=${WORKER_TRIES}"
  "--timeout=${WORKER_TIMEOUT}"
  "--max-time=${WORKER_MAX_TIME}"
  "--memory=${WORKER_MEMORY}"
)

echo "+ ${PHP_BIN} ${ARGS[*]}"
exec "$PHP_BIN" "${ARGS[@]}"
