#!/usr/bin/env bash
##
# Download DB dump via CURL.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
VORTEX_DB_DOWNLOAD_CURL_URL="${VORTEX_DB_DOWNLOAD_CURL_URL:-}"

# Directory with database dump file.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Database dump file name.
VORTEX_DB_FILE="${VORTEX_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database dump download from CURL."

# Check all required values.
[ -z "${VORTEX_DB_DOWNLOAD_CURL_URL}" ] && echo "Missing required value for VORTEX_DB_DOWNLOAD_CURL_URL." && exit 1

mkdir -p "${VORTEX_DB_DIR}"

curl -L "${VORTEX_DB_DOWNLOAD_CURL_URL}" -o "${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"

pass "Finished database dump download from CURL."
