#!/usr/bin/env bash
##
# Download DB dump from FTP.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Database index suffix. When set (e.g., "2"), all DB-related variable lookups
# use the indexed variant.
_db_index="${VORTEX_DB_INDEX:-}"

# The FTP user.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_USER"
VORTEX_DOWNLOAD_DB_FTP_USER="${!_v:-}"

# The FTP password.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_PASS"
VORTEX_DOWNLOAD_DB_FTP_PASS="${!_v:-}"

# The FTP host.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_HOST"
VORTEX_DOWNLOAD_DB_FTP_HOST="${!_v:-}"

# The FTP port.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_PORT"
VORTEX_DOWNLOAD_DB_FTP_PORT="${!_v:-}"

# The file name, including any directories.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_FILE"
VORTEX_DOWNLOAD_DB_FTP_FILE="${!_v:-}"

# Directory with database dump file.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_DB_DIR"
_vs="VORTEX_DOWNLOAD_DB${_db_index}_DIR"
_vss="VORTEX_DB${_db_index}_DIR"
VORTEX_DOWNLOAD_DB_FTP_DB_DIR="${!_v:-${!_vs:-${!_vss:-./.data}}}"

# Database dump file name.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FTP_DB_FILE"
_vs="VORTEX_DOWNLOAD_DB${_db_index}_FILE"
_vss="VORTEX_DB${_db_index}_FILE"
VORTEX_DOWNLOAD_DB_FTP_DB_FILE="${!_v:-${!_vs:-${!_vss:-db.sql}}}"

#-------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

# Check all required values.
[ -z "${VORTEX_DOWNLOAD_DB_FTP_USER}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_FTP_USER." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_FTP_PASS}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_FTP_PASS." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_FTP_HOST}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_FTP_HOST." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_FTP_PORT}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_FTP_PORT." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_FTP_FILE}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_FTP_FILE." && exit 1

info "Started database dump download from FTP."

mkdir -p "${VORTEX_DOWNLOAD_DB_FTP_DB_DIR}"

curl -u "${VORTEX_DOWNLOAD_DB_FTP_USER}":"${VORTEX_DOWNLOAD_DB_FTP_PASS}" "ftp://${VORTEX_DOWNLOAD_DB_FTP_HOST}:${VORTEX_DOWNLOAD_DB_FTP_PORT}/${VORTEX_DOWNLOAD_DB_FTP_FILE}" -o "${VORTEX_DOWNLOAD_DB_FTP_DB_DIR}/${VORTEX_DOWNLOAD_DB_FTP_DB_FILE}"

pass "Finished database dump download from FTP."
