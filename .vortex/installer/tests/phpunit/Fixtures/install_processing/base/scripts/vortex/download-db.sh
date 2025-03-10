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
VORTEX_DB_DOWNLOAD_SOURCE="${VORTEX_DB_DOWNLOAD_SOURCE:-curl}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
VORTEX_DB_DOWNLOAD_FORCE="${VORTEX_DB_DOWNLOAD_FORCE:-}"

# Proceed with download.
VORTEX_DB_DOWNLOAD_PROCEED="${VORTEX_DB_DOWNLOAD_PROCEED:-1}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started database download."

[ "${VORTEX_DB_DOWNLOAD_PROCEED}" != "1" ] && pass "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Check if database file exists.
# @todo: Implement better support based on $VORTEX_DB_FILE instead of hardcoded 'db*' name.
[ -d "${VORTEX_DB_DIR:-}" ] && found_db=$(find "${VORTEX_DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db:-}" ]; then
  note "Found existing database dump file(s)."
  ls -Alh "${VORTEX_DB_DIR}" || true

  if [ "${VORTEX_DB_DOWNLOAD_FORCE}" != "1" ]; then
    note "Using existing database dump file(s)."
    note "Download will not proceed."
    note "Remove existing database file or set VORTEX_DB_DOWNLOAD_FORCE value to 1 to force download."
    exit 0
  else
    note "Forcefully downloading database."
  fi
fi

if [ "${VORTEX_DB_DOWNLOAD_SOURCE}" = "ftp" ]; then
  ./scripts/vortex/download-db-ftp.sh
fi

if [ "${VORTEX_DB_DOWNLOAD_SOURCE}" = "curl" ]; then
  ./scripts/vortex/download-db-curl.sh
fi

if [ "${VORTEX_DB_DOWNLOAD_SOURCE}" = "acquia" ]; then
  ./scripts/vortex/download-db-acquia.sh
fi

if [ "${VORTEX_DB_DOWNLOAD_SOURCE}" = "lagoon" ]; then
  ./scripts/vortex/download-db-lagoon.sh
fi

if [ "${VORTEX_DB_DOWNLOAD_SOURCE}" = "container_registry" ]; then
  ./scripts/vortex/download-db-container-registry.sh
fi

ls -Alh "${VORTEX_DB_DIR}" || true

# Create a semaphore file to indicate that the database has been downloaded.
[ -n "${VORTEX_DB_DOWNLOAD_SEMAPHORE:-}" ] && touch "${VORTEX_DB_DOWNLOAD_SEMAPHORE}"

pass "Finished database download."
