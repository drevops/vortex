#!/usr/bin/env bash
##
# Download database dump.
#
# Download is supported from FTP, CURL or Acquia Cloud.
#
# This is a router script to call relevant database download scripts based on type.
#
# For required variables based on the download type,
# see ./scripts/drevops/download-db-[type].sh file.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Where the database is downloaded from:
# - "url" - directly from URL as a file using CURL.
# - "ftp" - directly from FTP as a file using CURL.
# - "acquia" - from latest Acquia backup via Cloud API as a file.
# - "docker_registry" - from the docker registry as a docker image.
# - "none" - not downloaded, site is freshly installed for every build.
#
# Note that "docker_registry" works only for database-in-Docker-image
# database storage (when DREVOPS_DATABASE_IMAGE variable has a value).
DREVOPS_DATABASE_DOWNLOAD_SOURCE="${DREVOPS_DATABASE_DOWNLOAD_SOURCE:-curl}"

# Flag to download a fresh copy of the database dump if the methods supports it.
DREVOPS_DB_DOWNLOAD_REFRESH="${DREVOPS_DB_DOWNLOAD_REFRESH:-}"

# Flag to force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
FORCE_DB_DOWNLOAD="${FORCE_DB_DOWNLOAD:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# Kill-switch to proceed with download.
DB_DOWNLOAD_PROCEED="${DB_DOWNLOAD_PROCEED:-1}"

# ------------------------------------------------------------------------------

# Post process command or a script used for running after the database was downloaded.
DOWNLOAD_POST_PROCESS="${DOWNLOAD_POST_PROCESS:-}"

# Kill-switch to proceed with download.
[ "${DB_DOWNLOAD_PROCEED}" -ne 1 ] && echo "==> Skipping database download as $DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Check provided download type.
[ -z "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" ] && echo "ERROR: Missing required value for DREVOPS_DATABASE_DOWNLOAD_SOURCE. Must be one of: ftp, curl, acquia, lagoon, docker_registry." && exit 1

# Check if database file exists.
# @todo: Implement better support based on $DREVOPS_DB_FILE.
[ -d "${DREVOPS_DB_DIR}" ] && found_db=$(find "${DREVOPS_DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db}" ]; then
  echo "==> Found existing database dump file(s)."
  ls -Alh "${DREVOPS_DB_DIR}"

  if [ -z "${FORCE_DB_DOWNLOAD}" ] ; then
    echo "==> Using existing database dump file(s). Download will not proceed. Remove existing database file or set FORCE_DB_DOWNLOAD flag to force download." && exit 0
  else
    echo "==> Forcefully downloading database."
  fi
fi

mkdir -p "${DREVOPS_DB_DIR}"

# Export DB dir and file variables as they are used in child scripts.
export DREVOPS_DB_DIR
export DREVOPS_DB_FILE
export DREVOPS_DB_DOWNLOAD_REFRESH

if [ "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" = "ftp" ]; then
  echo "==> Starting database dump download from FTP."
  ./scripts/drevops/download-db-ftp.sh
fi

if [ "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" = "curl" ]; then
  echo "==> Starting database dump download from CURL."
  ./scripts/drevops/download-db-curl.sh
fi

if [ "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" = "acquia" ]; then
  echo "==> Starting database dump download from Acquia."
  ./scripts/drevops/download-db-acquia.sh
fi

if [ "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" = "lagoon" ]; then
  echo "==> Starting database dump download from Lagoon."
  export DREVOPS_DB_LAGOON_PROJECT="${DREVOPS_DB_LAGOON_PROJECT:-${DREVOPS_LAGOON_PROJECT}}"
  export DREVOPS_DB_LAGOON_SSH_KEY_FILE="${DREVOPS_DB_LAGOON_SSH_KEY_FILE:-${DREVOPS_LAGOON_SSH_KEY_FILE}}"
  ./scripts/drevops/download-db-lagoon.sh
fi

if [ "${DREVOPS_DATABASE_DOWNLOAD_SOURCE}" = "docker_registry" ]; then
  echo "==> Starting database dump download from Docker Registry."
  ./scripts/drevops/download-db-image.sh "${DREVOPS_DATABASE_IMAGE:-}"
fi

echo "==> Downloaded database dump file in ${DREVOPS_DB_DIR}."
ls -Alh "${DREVOPS_DB_DIR}"

if [ -n "${DOWNLOAD_POST_PROCESS}" ]; then
  echo "==> Running database post download processing command(s) '${DOWNLOAD_POST_PROCESS}'."
  eval "${DOWNLOAD_POST_PROCESS}"
fi
