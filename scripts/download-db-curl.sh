#!/usr/bin/env bash
##
# Download DB dump via CURL.
#

set -e

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
CURL_DB_URL="${CURL_DB_URL:-}"

# Downloaded database dump file.
DB_FILE="${DB_FILE:-.data/db.sql}"

#-------------------------------------------------------------------------------

# Check all required values.
[ -z "${CURL_DB_URL}" ] && echo "Missing required value for CURL_DB_URL" && exit 1

mkdir -p "$(dirname "${DB_FILE}")"

curl -L "${CURL_DB_URL}" -o "${DB_FILE}"
