#!/usr/bin/env bash
##
# Manual test script for Google Drive database fetch.
#
# Fetches a database dump file from a Google Drive folder using
# fetch-db-gdrive.
#
# Usage:
#   export GDRIVE_SERVICE_ACCOUNT_KEY="/path/to/service-account.json"
#   export GDRIVE_FOLDER_ID="your-folder-id"
#   ./try-fetch-db-gdrive.sh

set -eu

# Source secrets if available.
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Get the directory of this script and navigate to project root.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"

# Google Drive credentials and settings.
GDRIVE_SERVICE_ACCOUNT_KEY="${GDRIVE_SERVICE_ACCOUNT_KEY:-}"
GDRIVE_FOLDER_ID="${GDRIVE_FOLDER_ID:-}"

# Validate required variables.
if [ -z "${GDRIVE_SERVICE_ACCOUNT_KEY}" ]; then
  echo "Error: GDRIVE_SERVICE_ACCOUNT_KEY environment variable is required"
  echo "Usage: export GDRIVE_SERVICE_ACCOUNT_KEY=\"/path/to/service-account.json\" && $0"
  exit 1
fi

if [ -z "${GDRIVE_FOLDER_ID}" ]; then
  echo "Error: GDRIVE_FOLDER_ID environment variable is required"
  echo "Usage: export GDRIVE_FOLDER_ID=\"your-folder-id\" && $0"
  exit 1
fi

echo "Testing Google Drive database fetch..."
echo ""
echo "Folder ID:  ${GDRIVE_FOLDER_ID}"
echo "DB file:    ${VORTEX_DB_FILE:-db.sql}"
echo "Local dir:  ${VORTEX_DB_DIR:-./.data}"
echo ""

cd "${PROJECT_ROOT}" || exit 1

export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${GDRIVE_SERVICE_ACCOUNT_KEY}"
export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="${GDRIVE_FOLDER_ID}"

./vendor/drevops/vortex-tooling/src/vortex-fetch-db-gdrive

echo ""
echo "Fetch complete!"
