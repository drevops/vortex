#!/usr/bin/env bash
##
# Manual test script for FTP database fetch.
#
# Downloads the latest database dump file from an FTP server using
# fetch-db-ftp, for example with a read-only Storage Box subaccount.
#
# Usage:
#   export FTP_HOST="ftp.example.com"
#   export FTP_PORT="21"
#   export FTP_USER="your-read-user"
#   export FTP_PASS="your-read-password"
#   export FTP_FILE="path/to/db.sql"
#   ./try-fetch-db-ftp.sh

set -eu

# Source secrets if available.
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Get the directory of this script and navigate to project root.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"

# FTP credentials and settings.
FTP_HOST="${FTP_HOST:-}"
FTP_PORT="${FTP_PORT:-21}"
FTP_USER="${FTP_USER:-}"
FTP_PASS="${FTP_PASS:-}"
FTP_FILE="${FTP_FILE:-db.sql}"

# Validate required variables.
for name in FTP_HOST FTP_USER FTP_PASS; do
  if [ -z "${!name}" ]; then
    echo "Error: ${name} environment variable is required"
    echo "Usage: export ${name}=\"...\" && $0"
    exit 1
  fi
done

db_dir="${VORTEX_DB_DIR:-./.data}"
db_file="${VORTEX_DB_FILE:-db.sql}"

echo "Testing FTP database fetch..."
echo ""
echo "FTP host:   ${FTP_HOST}:${FTP_PORT}"
echo "FTP file:   ${FTP_FILE}"
echo "Local file: ${db_dir}/${db_file}"
echo ""

cd "${PROJECT_ROOT}" || exit 1

export VORTEX_FETCH_DB_FTP_HOST="${FTP_HOST}"
export VORTEX_FETCH_DB_FTP_PORT="${FTP_PORT}"
export VORTEX_FETCH_DB_FTP_USER="${FTP_USER}"
export VORTEX_FETCH_DB_FTP_PASS="${FTP_PASS}"
export VORTEX_FETCH_DB_FTP_FILE="${FTP_FILE}"

./vendor/drevops/vortex-tooling/src/vortex-fetch-db-ftp

echo ""
echo "Fetch complete!"
