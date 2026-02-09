#!/usr/bin/env bash
##
# Manual test script for S3 database download.
#
# Downloads a database dump file from an S3 bucket using download-db-s3.sh.
#
# Usage:
#   export S3_ACCESS_KEY="your-access-key"
#   export S3_SECRET_KEY="your-secret-key"
#   export S3_BUCKET="your-bucket"
#   export S3_REGION="ap-southeast-2"
#   export S3_PREFIX="path/to/folder/"
#   ./try-s3-download.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available.
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Get the directory of this script and navigate to project root.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"

# S3 credentials and settings.
S3_ACCESS_KEY="${S3_ACCESS_KEY:-}"
S3_SECRET_KEY="${S3_SECRET_KEY:-}"
S3_BUCKET="${S3_BUCKET:-}"
S3_REGION="${S3_REGION:-ap-southeast-2}"
S3_PREFIX="${S3_PREFIX:-}"

# Validate required variables.
if [ -z "${S3_ACCESS_KEY}" ]; then
  echo "Error: S3_ACCESS_KEY environment variable is required"
  echo "Usage: export S3_ACCESS_KEY=\"your-access-key\" && $0"
  exit 1
fi

if [ -z "${S3_SECRET_KEY}" ]; then
  echo "Error: S3_SECRET_KEY environment variable is required"
  echo "Usage: export S3_SECRET_KEY=\"your-secret-key\" && $0"
  exit 1
fi

if [ -z "${S3_BUCKET}" ]; then
  echo "Error: S3_BUCKET environment variable is required"
  echo "Usage: export S3_BUCKET=\"your-bucket\" && $0"
  exit 1
fi

echo "Testing S3 database download..."
echo ""
echo "S3 bucket:  ${S3_BUCKET}"
echo "S3 region:  ${S3_REGION}"
[ -n "${S3_PREFIX}" ] && echo "S3 prefix:  ${S3_PREFIX}"
echo "DB file:    ${VORTEX_DB_FILE:-db.sql}"
echo "Local dir:  ${VORTEX_DB_DIR:-./.data}"
echo ""

cd "${PROJECT_ROOT}" || exit 1

export VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY="${S3_ACCESS_KEY}"
export VORTEX_DB_DOWNLOAD_S3_SECRET_KEY="${S3_SECRET_KEY}"
export VORTEX_DB_DOWNLOAD_S3_BUCKET="${S3_BUCKET}"
export VORTEX_DB_DOWNLOAD_S3_REGION="${S3_REGION}"
export VORTEX_DB_DOWNLOAD_S3_PREFIX="${S3_PREFIX}"

./scripts/vortex/download-db-s3.sh

echo ""
echo "Download complete!"
