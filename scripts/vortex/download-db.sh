#!/usr/bin/env bash
##
# Download database dump.
#
# This is a router script to call relevant scripts based on type.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Database index suffix. When set (e.g., "2"), all DB-related variable lookups
# use the indexed variant (e.g., VORTEX_DOWNLOAD_DB2_SOURCE instead of
# VORTEX_DOWNLOAD_DB_SOURCE).
_db_index="${VORTEX_DB_INDEX:-}"

# Note that `container_registry` works only for database-in-image
# database storage (when $VORTEX_DB_IMAGE variable has a value).
_v="VORTEX_DOWNLOAD_DB${_db_index}_SOURCE"
VORTEX_DOWNLOAD_DB_SOURCE="${!_v:-url}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FORCE"
VORTEX_DOWNLOAD_DB_FORCE="${!_v:-}"

# Proceed with download.
_v="VORTEX_DOWNLOAD_DB${_db_index}_PROCEED"
VORTEX_DOWNLOAD_DB_PROCEED="${!_v:-1}"

# Database dump file name.
_v="VORTEX_DOWNLOAD_DB${_db_index}_FILE"
_vs="VORTEX_DB${_db_index}_FILE"
VORTEX_DOWNLOAD_DB_FILE="${!_v:-${!_vs:-db.sql}}"

# Directory with database dump file.
_v="VORTEX_DOWNLOAD_DB${_db_index}_DIR"
_vs="VORTEX_DB${_db_index}_DIR"
VORTEX_DOWNLOAD_DB_DIR="${!_v:-${!_vs:-./.data}}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started database${_db_index:+ ${_db_index}} download."

[ "${VORTEX_DOWNLOAD_DB_PROCEED}" != "1" ] && pass "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Skip file existence check for container_registry source as the database is
# stored as a Docker image, not a file.
if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" != "container_registry" ]; then
  db_file_basename="${VORTEX_DOWNLOAD_DB_FILE%.*}"
  [ -d "${VORTEX_DOWNLOAD_DB_DIR:-}" ] && found_db=$(find "${VORTEX_DOWNLOAD_DB_DIR}" -name "${db_file_basename}.sql" -o -name "${db_file_basename}.tar")

  if [ -n "${found_db:-}" ]; then
    note "Found existing database dump file(s)."
    ls -Alh "${VORTEX_DOWNLOAD_DB_DIR}" 2>/dev/null || true

    if [ "${VORTEX_DOWNLOAD_DB_FORCE}" != "1" ]; then
      note "Using existing database dump file(s)."
      note "Download will not proceed."
      note "Remove existing database file or set VORTEX_DOWNLOAD_DB_FORCE value to 1 to force download."
      exit 0
    else
      note "Will download a fresh copy of the database."
    fi
  fi
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "ftp" ]; then
  ./scripts/vortex/download-db-ftp.sh
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "url" ]; then
  ./scripts/vortex/download-db-url.sh
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "acquia" ]; then
  ./scripts/vortex/download-db-acquia.sh
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "lagoon" ]; then
  ./scripts/vortex/download-db-lagoon.sh
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "container_registry" ]; then
  ./scripts/vortex/download-db-container-registry.sh
fi

if [ "${VORTEX_DOWNLOAD_DB_SOURCE}" = "s3" ]; then
  ./scripts/vortex/download-db-s3.sh
fi

ls -Alh "${VORTEX_DOWNLOAD_DB_DIR}" || true

# Create a semaphore file to indicate that the database has been downloaded.
[ -n "${VORTEX_DOWNLOAD_DB_SEMAPHORE:-}" ] && touch "${VORTEX_DOWNLOAD_DB_SEMAPHORE}"

pass "Finished database${_db_index:+ ${_db_index}} download."
