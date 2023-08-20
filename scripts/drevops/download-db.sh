#!/usr/bin/env bash
##
# Download database dump.
#
# This is a router script to call relevant scripts based on type.
#
# IMPORTANT! This script runs outside the container on the host system.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Note that `docker_registry` works only for database-in-Docker-image
# database storage (when $DREVOPS_DB_DOCKER_IMAGE variable has a value).
DREVOPS_DB_DOWNLOAD_SOURCE="${DREVOPS_DB_DOWNLOAD_SOURCE:-curl}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
DREVOPS_DB_DOWNLOAD_FORCE="${DREVOPS_DB_DOWNLOAD_FORCE:-}"

# Proceed with download.
DREVOPS_DB_DOWNLOAD_PROCEED="${DREVOPS_DB_DOWNLOAD_PROCEED:-1}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started database download."

[ "${DREVOPS_DB_DOWNLOAD_PROCEED}" != "1" ] && pass "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Check if database file exists.
# @todo: Implement better support based on $DREVOPS_DB_FILE instead of hardcoded 'db*' name.
[ -d "${DREVOPS_DB_DIR:-}" ] && found_db=$(find "${DREVOPS_DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db:-}" ]; then
  note "Found existing database dump file(s)."
  ls -Alh "${DREVOPS_DB_DIR}" || true

  if [ "${DREVOPS_DB_DOWNLOAD_FORCE}" != "1" ]; then
    note "Using existing database dump file(s)."
    note "Download will not proceed."
    note "Remove existing database file or set DREVOPS_DB_DOWNLOAD_FORCE value to 1 to force download."
    exit 0
  else
    note "Forcefully downloading database."
  fi
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "ftp" ]; then
  ./scripts/drevops/download-db-ftp.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "curl" ]; then
  ./scripts/drevops/download-db-curl.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "acquia" ]; then
  ./scripts/drevops/download-db-acquia.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "lagoon" ]; then
  ./scripts/drevops/download-db-lagoon.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "docker_registry" ]; then
  ./scripts/drevops/download-db-docker-registry.sh
fi

ls -Alh "${DREVOPS_DB_DIR}" || true

# Create a semaphore file to indicate that the database has been downloaded.
[ -n "${DREVOPS_DB_DOWNLOAD_SEMAPHORE:-}" ] && touch "${DREVOPS_DB_DOWNLOAD_SEMAPHORE}"

pass "Finished database download."
