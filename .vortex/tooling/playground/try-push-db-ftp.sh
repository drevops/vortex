#!/usr/bin/env bash
##
# Manual test script for FTP database push.
#
# Uploads a database dump file to an FTP server using push-db-ftp. Run it
# twice to check that the second run keeps the first dump as
# 'db-previous.sql' next to the new 'db.sql'.
#
# Usage:
#   export FTP_HOST="ftp.example.com"
#   export FTP_PORT="21"
#   export FTP_USER="your-user"
#   export FTP_PASS="your-password"
#   export FTP_FILE="path/to/db.sql"
#   ./try-push-db-ftp.sh

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

cd "${PROJECT_ROOT}" || exit 1

# Create a test dump file when none exists.
db_dir="${VORTEX_DB_DIR:-./.data}"
db_file="${VORTEX_DB_FILE:-db.sql}"
if [ ! -f "${db_dir}/${db_file}" ]; then
  mkdir -p "${db_dir}"
  echo "-- Test dump created by try-push-db-ftp.sh on $(date -u)" >"${db_dir}/${db_file}"
fi

echo "Testing FTP database push..."
echo ""
echo "FTP host:   ${FTP_HOST}:${FTP_PORT}"
echo "FTP file:   ${FTP_FILE}"
echo "Local file: ${db_dir}/${db_file}"
echo ""

export VORTEX_PUSH_DB_FTP_HOST="${FTP_HOST}"
export VORTEX_PUSH_DB_FTP_PORT="${FTP_PORT}"
export VORTEX_PUSH_DB_FTP_USER="${FTP_USER}"
export VORTEX_PUSH_DB_FTP_PASS="${FTP_PASS}"
export VORTEX_PUSH_DB_FTP_FILE="${FTP_FILE}"

./vendor/drevops/vortex-tooling/src/vortex-push-db-ftp

echo ""
echo "Push complete!"
