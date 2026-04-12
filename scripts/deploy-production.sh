#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$(cd -- "${SCRIPT_DIR}/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
NPX_BIN="${NPX_BIN:-npx}"
RUN_GIT_PULL="${RUN_GIT_PULL:-1}"
INSTALL_NODE_DEPS="${INSTALL_NODE_DEPS:-1}"
INSTALL_PLAYWRIGHT="${INSTALL_PLAYWRIGHT:-1}"
PLAYWRIGHT_WITH_DEPS="${PLAYWRIGHT_WITH_DEPS:-0}"
TARGET_BRANCH="${BRANCH:-}"

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Required command not found: $1" >&2
    exit 1
  fi
}

run() {
  echo "+ $*"
  "$@"
}

artisan() {
  run "$PHP_BIN" artisan "$@"
}

require_command git
require_command "$PHP_BIN"
require_command "$COMPOSER_BIN"

if [ "${INSTALL_NODE_DEPS}" = "1" ] || [ "${INSTALL_PLAYWRIGHT}" = "1" ]; then
  require_command "$NPM_BIN"
  require_command "$NPX_BIN"
fi

cd "$APP_DIR"

if [ ! -f artisan ]; then
  echo "Could not find artisan in ${APP_DIR}" >&2
  exit 1
fi

if [ ! -f .env ]; then
  echo "Missing .env in ${APP_DIR}. Create the production environment file before deploy." >&2
  exit 1
fi

if [ "${RUN_GIT_PULL}" = "1" ]; then
  if [ -z "${TARGET_BRANCH}" ]; then
    TARGET_BRANCH="$(git branch --show-current)"
  fi

  if [ -z "${TARGET_BRANCH}" ]; then
    echo "Could not determine the git branch to deploy. Set BRANCH=<name>." >&2
    exit 1
  fi

  run git fetch origin "${TARGET_BRANCH}"

  CURRENT_BRANCH="$(git branch --show-current)"
  if [ "${CURRENT_BRANCH}" != "${TARGET_BRANCH}" ]; then
    run git checkout "${TARGET_BRANCH}"
  fi

  run git pull --ff-only origin "${TARGET_BRANCH}"
fi

run "$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader --no-dev

if [ "${INSTALL_NODE_DEPS}" = "1" ]; then
  run "$NPM_BIN" ci --include=dev
  run "$NPM_BIN" run build
fi

if [ "${INSTALL_PLAYWRIGHT}" = "1" ]; then
  if [ "${PLAYWRIGHT_WITH_DEPS}" = "1" ]; then
    run "$NPX_BIN" playwright install --with-deps chromium
  else
    run "$NPX_BIN" playwright install chromium
  fi
fi

artisan migrate --force
artisan storage:link || true
artisan optimize:clear
artisan config:cache
artisan view:cache

echo
echo "Production deploy completed."
echo "Next steps:"
echo "  1. Install the scheduler cron with scripts/install-scheduler-cron.sh"
echo "  2. Run the queue worker via scripts/run-notification-worker.sh"
