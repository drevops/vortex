#!/usr/bin/env bash
##
# Download DB dump via CURL.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
CURL_DB_URL="${CURL_DB_URL:-}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# Check all required values.
[ -z "${CURL_DB_URL}" ] && echo "Missing required value for CURL_DB_URL." && exit 1

curl -L "${CURL_DB_URL}" -o "${DB_DIR}/${DB_FILE}"
