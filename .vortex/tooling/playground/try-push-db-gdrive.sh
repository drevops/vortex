#!/usr/bin/env bash
##
# Manual test script for Google Drive database push.
#
# Uploads a database dump file to a Google Drive folder in a shared drive
# using push-db-gdrive. Run it twice to check that the second run replaces
# the file instead of creating a duplicate.
#
# Usage:
#   export GDRIVE_SERVICE_ACCOUNT_KEY="/path/to/service-account.json"
#   export GDRIVE_FOLDER_ID="your-folder-id"
#   ./try-push-db-gdrive.sh

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

cd "${PROJECT_ROOT}" || exit 1

# Create a test dump file when none exists.
db_dir="${VORTEX_DB_DIR:-./.data}"
db_file="${VORTEX_DB_FILE:-db.sql}"
if [ ! -f "${db_dir}/${db_file}" ]; then
  mkdir -p "${db_dir}"
  echo "-- Test dump created by try-push-db-gdrive.sh on $(date -u)" >"${db_dir}/${db_file}"
fi

echo "Testing Google Drive database push..."
echo ""
echo "Folder ID:  ${GDRIVE_FOLDER_ID}"
echo "Local file: ${db_dir}/${db_file}"
echo ""

export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${GDRIVE_SERVICE_ACCOUNT_KEY}"
export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="${GDRIVE_FOLDER_ID}"

./vendor/drevops/vortex-tooling/src/vortex-push-db-gdrive

echo ""
echo "Push complete!"
