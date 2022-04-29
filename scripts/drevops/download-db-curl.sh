#!/usr/bin/env bash
##
# Download DB dump via CURL.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
DREVOPS_CURL_DB_URL="${DREVOPS_CURL_DB_URL:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# Check all required values.
[ -z "${DREVOPS_CURL_DB_URL}" ] && echo "Missing required value for DREVOPS_CURL_DB_URL." && exit 1

curl -L "${DREVOPS_CURL_DB_URL}" -o "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
