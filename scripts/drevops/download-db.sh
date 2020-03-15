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

# The type of database dump download. Can be one of: ftp, curl, acquia.
# Defaulting to CURL to allow using of demo DB.
DATABASE_DOWNLOAD_SOURCE="${DATABASE_DOWNLOAD_SOURCE:-curl}"

# Flag to force DB download even if the cache exists.
# Usually in CircleCI UI to override per build cache.
FORCE_DB_DOWNLOAD="${FORCE_DB_DOWNLOAD:-}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

# Kill-switch to proceed with download.
DB_DOWNLOAD_PROCEED="${DB_DOWNLOAD_PROCEED:-1}"

# ------------------------------------------------------------------------------

# Post process command or a script used for running after the database was downloaded.
DOWNLOAD_POST_PROCESS="${DOWNLOAD_POST_PROCESS:-}"

# Kill-switch to proceed with download.
[ "${DB_DOWNLOAD_PROCEED}" -ne 1 ] && echo "==> Skipping database download as $DB_DOWNLOAD_PROCEED is not set to 1" && exit 0

# Check provided download type.
[ -z "${DATABASE_DOWNLOAD_SOURCE}" ] && echo "ERROR: Missing required value for DATABASE_DOWNLOAD_SOURCE. Must be one of: ftp, curl, acquia, docker_image." && exit 1

# Check if database file exists.
[ -d "${DB_DIR}" ] && found_db=$(find "${DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db}" ]; then
  echo "==> Found existing database dump file ${found_db}"
  ls -alh "${DB_DIR}/${DB_FILE}"

  if [ -z "${FORCE_DB_DOWNLOAD}" ] ; then
    echo "==> Using existing database dump file ${found_db}. Download will not proceed. Remove existing database file or set FORCE_DB_DOWNLOAD flag to force download." && exit 0
  else
    echo "==> Forcefully downloading database"
  fi
fi

mkdir -p "${DB_DIR}"

# Export DB dir and file variables as they are used in child scripts.
export DB_DIR
export DB_FILE

if [ "${DATABASE_DOWNLOAD_SOURCE}" == "ftp" ]; then
  echo "==> Starting database dump download from FTP"
  ./scripts/drevops/download-db-ftp.sh
fi

if [ "${DATABASE_DOWNLOAD_SOURCE}" == "curl" ]; then
  echo "==> Starting database dump download from CURL"
  ./scripts/drevops/download-db-curl.sh
fi

if [ "${DATABASE_DOWNLOAD_SOURCE}" == "acquia" ]; then
  echo "==> Starting database dump download from Acquia"
  ./scripts/drevops/download-db-acquia.sh
fi

if [ "${DATABASE_DOWNLOAD_SOURCE}" == "docker_registry" ]; then
  echo "==> Starting database dump download from Docker Registry"
  ./scripts/drevops/download-db-image.sh "${DATABASE_IMAGE:-}"
fi

echo "==> Downloaded database dump file in ${DB_DIR}"
ls -alh "${DB_DIR}"

if [ -n "${DOWNLOAD_POST_PROCESS}" ]; then
  echo "==> Running database post download processing command(s) '${DOWNLOAD_POST_PROCESS}'"
  eval "${DOWNLOAD_POST_PROCESS}"
fi
