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

# Database index suffix. When set (e.g., "2"), all DB-related variable lookups
# use the indexed variant.
_db_index="${VORTEX_DB_INDEX:-}"

# URL of the remote database. If HTTP authentication required, it must be
# included in the variable.
_v="VORTEX_DOWNLOAD_DB${_db_index}_URL"
VORTEX_DOWNLOAD_DB_URL="${!_v:-}"

# Directory with database dump file.
_v="VORTEX_DOWNLOAD_DB${_db_index}_URL_DB_DIR"
_vs="VORTEX_DOWNLOAD_DB${_db_index}_DIR"
_vss="VORTEX_DB${_db_index}_DIR"
VORTEX_DOWNLOAD_DB_URL_DB_DIR="${!_v:-${!_vs:-${!_vss:-./.data}}}"

# Database dump file name.
_v="VORTEX_DOWNLOAD_DB${_db_index}_URL_DB_FILE"
_vs="VORTEX_DOWNLOAD_DB${_db_index}_FILE"
_vss="VORTEX_DB${_db_index}_FILE"
VORTEX_DOWNLOAD_DB_URL_DB_FILE="${!_v:-${!_vs:-${!_vss:-db.sql}}}"

# Password for unzipping password-protected zip files.
_v="VORTEX_DOWNLOAD_DB${_db_index}_UNZIP_PASSWORD"
VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD="${!_v:-}"

#-------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in curl unzip; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database dump download from URL."

# Check all required values.
[ -z "${VORTEX_DOWNLOAD_DB_URL}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_URL." && exit 1

mkdir -p "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}"

note "Downloading database dump file."
curl -Ls "${VORTEX_DOWNLOAD_DB_URL}" -o "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}"

if [ "${VORTEX_DOWNLOAD_DB_URL%*.zip}" != "${VORTEX_DOWNLOAD_DB_URL}" ]; then
  note "Detecting zip file, preparing for extraction."
  mv "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}" "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}.zip"

  # Create temporary directory for extraction
  temp_extract_dir="${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/tmp_extract_$$"
  mkdir -p "${temp_extract_dir}"

  if [ -n "${VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD}" ]; then
    note "Unzipping password-protected database dump file."
    unzip -o -P "${VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD}" "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}.zip" -d "${temp_extract_dir}"
  else
    note "Unzipping database dump file."
    unzip -o "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}.zip" -d "${temp_extract_dir}"
  fi

  # Find the first regular file (not directory) in the extracted content.
  note "Discovering database file in archive."
  extracted_file=$(find "${temp_extract_dir}" -type f -print | head -n 1)

  if [ -z "${extracted_file}" ]; then
    fail "No files found in the zip archive."
    rm -rf "${temp_extract_dir}" >/dev/null
    rm -f "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}.zip" >/dev/null
    exit 1
  fi

  note "Moving extracted file to target location."
  mv "${extracted_file}" "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}"

  note "Cleaning up temporary files."
  rm -rf "${temp_extract_dir}" >/dev/null
  rm -f "${VORTEX_DOWNLOAD_DB_URL_DB_DIR}/${VORTEX_DOWNLOAD_DB_URL_DB_FILE}.zip" >/dev/null
fi

pass "Finished database dump download from URL."
