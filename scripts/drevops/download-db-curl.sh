#!/usr/bin/env bash
##
# Download DB dump via CURL.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
DREVOPS_DB_DOWNLOAD_CURL_URL="${DREVOPS_DB_DOWNLOAD_CURL_URL:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started database dump download from CURL."

# Check all required values.
[ -z "${DREVOPS_DB_DOWNLOAD_CURL_URL}" ] && echo "Missing required value for DREVOPS_DB_DOWNLOAD_CURL_URL." && exit 1

mkdir -p "${DREVOPS_DB_DIR}"

curl -L "${DREVOPS_DB_DOWNLOAD_CURL_URL}" -o "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

pass "Started database dump download from CURL."
