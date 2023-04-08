#!/usr/bin/env bash
##
# Download database dump.
#
# Download is supported from FTP, CURL or Acquia Cloud.
#
# This is a router script to call relevant scripts based on type.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Note that "docker_registry" works only for database-in-Docker-image
# database storage (when DREVOPS_DB_DOCKER_IMAGE variable has a value).
DREVOPS_DB_DOWNLOAD_SOURCE="${DREVOPS_DB_DOWNLOAD_SOURCE:-curl}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
DREVOPS_DB_DOWNLOAD_FORCE="${DREVOPS_DB_DOWNLOAD_FORCE:-}"

# Kill-switch to proceed with download.
DREVOPS_DB_DOWNLOAD_PROCEED="${DREVOPS_DB_DOWNLOAD_PROCEED:-1}"

# Post process command or a script used for running after the database was downloaded.
DREVOPS_DB_DOWNLOAD_POST_PROCESS="${DREVOPS_DB_DOWNLOAD_POST_PROCESS:-}"

# ------------------------------------------------------------------------------

echo "[INFO] Started database download."

# Kill-switch to proceed with download.
[ "${DREVOPS_DB_DOWNLOAD_PROCEED}" -ne 1 ] && echo "  [OK] Skipping database download as $DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Check if database file exists.
# @todo: Implement better support based on $DREVOPS_DB_FILE instead of hardcoded 'db*' name.
[ -d "${DREVOPS_DB_DIR}" ] && found_db=$(find "${DREVOPS_DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db}" ]; then
  echo "    > Found existing database dump file(s)."
  ls -Alh "${DREVOPS_DB_DIR}"

  if [ -z "${DREVOPS_DB_DOWNLOAD_FORCE}" ] ; then
    echo "     > Using existing database dump file(s)."
    echo "     > Download will not proceed."
    echo "     > Remove existing database file or set DREVOPS_DB_DOWNLOAD_FORCE flag to force download."
    exit 0
  else
    echo "     > Forcefully downloading database."
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
  ./scripts/drevops/download-db-image.sh
fi

echo "  [OK] Downloaded database dump file in ${DREVOPS_DB_DIR}."

ls -Alh "${DREVOPS_DB_DIR}"

if [ -n "${DREVOPS_DB_DOWNLOAD_POST_PROCESS}" ]; then
  echo "[INFO] Started running database post download processing command(s) '${DREVOPS_DB_DOWNLOAD_POST_PROCESS}'."
  eval "${DREVOPS_DB_DOWNLOAD_POST_PROCESS}"
  echo "  [OK] Finished Running database post download processing command(s) '${DREVOPS_DB_DOWNLOAD_POST_PROCESS}'."
fi
