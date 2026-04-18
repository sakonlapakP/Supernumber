#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$(cd -- "${SCRIPT_DIR}/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
CRON_LOG="${CRON_LOG:-/dev/null}"
BEGIN_MARKER="# supernumber scheduler begin"
END_MARKER="# supernumber scheduler end"

if ! command -v crontab >/dev/null 2>&1; then
  echo "Required command not found: crontab" >&2
  exit 1
fi

RESOLVED_PHP_BIN="$(command -v "$PHP_BIN" 2>/dev/null || true)"

if [ -z "${RESOLVED_PHP_BIN}" ]; then
  echo "Required command not found: $PHP_BIN" >&2
  exit 1
fi

CRON_LINE="* * * * * cd \"${APP_DIR}\" && \"${RESOLVED_PHP_BIN}\" artisan schedule:run >> \"${CRON_LOG}\" 2>&1"

CURRENT_CRONTAB="$(crontab -l 2>/dev/null || true)"
CLEANED_CRONTAB="$(printf '%s\n' "${CURRENT_CRONTAB}" | sed "/^${BEGIN_MARKER}$/,/^${END_MARKER}$/d")"

{
  if [ -n "${CLEANED_CRONTAB}" ]; then
    printf '%s\n' "${CLEANED_CRONTAB}"
  fi
  printf '%s\n' "${BEGIN_MARKER}"
  printf '%s\n' "${CRON_LINE}"
  printf '%s\n' "${END_MARKER}"
} | crontab -

echo "Installed scheduler cron:"
echo "${CRON_LINE}"
