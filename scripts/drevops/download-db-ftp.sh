#!/usr/bin/env bash
##
# Download DB dump from FTP.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The FTP user.
DREVOPS_DB_DOWNLOAD_FTP_USER="${DREVOPS_DB_DOWNLOAD_FTP_USER:-}"

# The FTP password.
DREVOPS_DB_DOWNLOAD_FTP_PASS="${DREVOPS_DB_DOWNLOAD_FTP_PASS:-}"

# The FTP host.
DREVOPS_DB_DOWNLOAD_FTP_HOST="${DREVOPS_DB_DOWNLOAD_FTP_HOST:-}"

# The FTP port.
DREVOPS_DB_DOWNLOAD_FTP_PORT="${DREVOPS_DB_DOWNLOAD_FTP_PORT:-}"

# The file name, including any directories.
DREVOPS_DB_DOWNLOAD_FTP_FILE="${DREVOPS_DB_DOWNLOAD_FTP_FILE:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

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

# Check all required values.
[ -z "${DREVOPS_DB_DOWNLOAD_FTP_USER}" ] && fail "Missing required value for DREVOPS_DB_DOWNLOAD_FTP_USER." && exit 1
[ -z "${DREVOPS_DB_DOWNLOAD_FTP_PASS}" ] && fail "Missing required value for DREVOPS_DB_DOWNLOAD_FTP_PASS." && exit 1
[ -z "${DREVOPS_DB_DOWNLOAD_FTP_HOST}" ] && fail "Missing required value for DREVOPS_DB_DOWNLOAD_FTP_HOST." && exit 1
[ -z "${DREVOPS_DB_DOWNLOAD_FTP_PORT}" ] && fail "Missing required value for DREVOPS_DB_DOWNLOAD_FTP_PORT." && exit 1
[ -z "${DREVOPS_DB_DOWNLOAD_FTP_FILE}" ] && fail "Missing required value for DREVOPS_DB_DOWNLOAD_FTP_FILE." && exit 1

info "Started database dump download from FTP."

mkdir -p "${DREVOPS_DB_DIR}"

curl -u "${DREVOPS_DB_DOWNLOAD_FTP_USER}":"${DREVOPS_DB_DOWNLOAD_FTP_PASS}" "ftp://${DREVOPS_DB_DOWNLOAD_FTP_HOST}:${DREVOPS_DB_DOWNLOAD_FTP_PORT}/${DREVOPS_DB_DOWNLOAD_FTP_FILE}" -o "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

pass "Finished database dump download from FTP."
