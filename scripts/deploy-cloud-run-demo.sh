#!/usr/bin/env bash
set -euo pipefail

PROJECT_ID="${1:-supernumber}"
REGION="${2:-asia-southeast1}"
SERVICE_NAME="${3:-supernumber-demo}"

if ! command -v gcloud >/dev/null 2>&1; then
  echo "gcloud CLI is required."
  exit 1
fi

APP_KEY="${APP_KEY:-base64:$(php -r 'echo base64_encode(random_bytes(32));')}"

echo "Deploying service: ${SERVICE_NAME}"
gcloud run deploy "${SERVICE_NAME}" \
  --project "${PROJECT_ID}" \
  --region "${REGION}" \
  --source . \
  --platform managed \
  --port 8080 \
  --allow-unauthenticated \
  --max-instances 1 \
  --set-env-vars "APP_KEY=${APP_KEY},DB_CONNECTION=sqlite,DB_DATABASE=/tmp/database.sqlite,SESSION_DRIVER=database,CACHE_STORE=database,QUEUE_CONNECTION=sync,FILESYSTEM_DISK=local,LINE_CHANNEL_ACCESS_TOKEN=,LINE_GROUP_ID="

SERVICE_URL="$(gcloud run services describe "${SERVICE_NAME}" --project "${PROJECT_ID}" --region "${REGION}" --format='value(status.url)')"
echo "Deployed URL: ${SERVICE_URL}"
echo "Demo login: manager / manager"
