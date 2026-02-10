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

# Note that `container_registry` works only for database-in-image
# database storage (when $VORTEX_DB_IMAGE variable has a value).
VORTEX_DOWNLOAD_DB_SOURCE="${VORTEX_DOWNLOAD_DB_SOURCE:-url}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
VORTEX_DOWNLOAD_DB_FORCE="${VORTEX_DOWNLOAD_DB_FORCE:-}"

# Proceed with download.
VORTEX_DOWNLOAD_DB_PROCEED="${VORTEX_DOWNLOAD_DB_PROCEED:-1}"

# Database dump file name.
VORTEX_DOWNLOAD_DB_FILE="${VORTEX_DOWNLOAD_DB_FILE:-${VORTEX_DB_FILE:-db.sql}}"

# Directory with database dump file.
VORTEX_DOWNLOAD_DB_DIR="${VORTEX_DOWNLOAD_DB_DIR:-${VORTEX_DB_DIR:-./.data}}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started database download."

[ "${VORTEX_DOWNLOAD_DB_PROCEED}" != "1" ] && pass "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

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

pass "Finished database download."
