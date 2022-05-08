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
# database storage (when DREVOPS_DB_DOCKER_IMAGE variable has a value).
DREVOPS_DB_DOWNLOAD_SOURCE="${DREVOPS_DB_DOWNLOAD_SOURCE:-curl}"

# Flag to download a fresh copy of the database dump if the methods supports it.
DREVOPS_DB_DOWNLOAD_REFRESH="${DREVOPS_DB_DOWNLOAD_REFRESH:-}"

# Force DB download even if the cache exists.
# Usually set in CircleCI UI to override per build cache.
DREVOPS_DB_DOWNLOAD_FORCE="${DREVOPS_DB_DOWNLOAD_FORCE:-}"

# Kill-switch to proceed with download.
DREVOPS_DB_DOWNLOAD_PROCEED="${DREVOPS_DB_DOWNLOAD_PROCEED:-1}"

# Post process command or a script used for running after the database was downloaded.
DREVOPS_DB_DOWNLOAD_POST_PROCESS="${DREVOPS_DB_DOWNLOAD_POST_PROCESS:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# ------------------------------------------------------------------------------

# Kill-switch to proceed with download.
[ "${DREVOPS_DB_DOWNLOAD_PROCEED}" -ne 1 ] && echo "==> Skipping database download as $DB_DOWNLOAD_PROCEED is not set to 1." && exit 0

# Check provided download type.
[ -z "${DREVOPS_DB_DOWNLOAD_SOURCE}" ] && echo "ERROR: Missing required value for DREVOPS_DB_DOWNLOAD_SOURCE. Must be one of: ftp, curl, acquia, lagoon, docker_registry." && exit 1

# Check if database file exists.
# @todo: Implement better support based on $DREVOPS_DB_FILE.
[ -d "${DREVOPS_DB_DIR}" ] && found_db=$(find "${DREVOPS_DB_DIR}" -name "db*.sql" -o -name "db*.tar")

if [ -n "${found_db}" ]; then
  echo "==> Found existing database dump file(s)."
  ls -Alh "${DREVOPS_DB_DIR}"

  if [ -z "${DREVOPS_DB_DOWNLOAD_FORCE}" ] ; then
    echo "==> Using existing database dump file(s). Download will not proceed. Remove existing database file or set DREVOPS_DB_DOWNLOAD_FORCE flag to force download." && exit 0
  else
    echo "==> Forcefully downloading database."
  fi
fi

mkdir -p "${DREVOPS_DB_DIR}"

# Export DB dir and file variables as they are used in child scripts.
export DREVOPS_DB_DIR
export DREVOPS_DB_FILE
export DREVOPS_DB_DOWNLOAD_REFRESH

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "ftp" ]; then
  echo "==> Started database dump download from FTP."
  ./scripts/drevops/download-db-ftp.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "curl" ]; then
  echo "==> Started database dump download from CURL."
  ./scripts/drevops/download-db-curl.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "acquia" ]; then
  ./scripts/drevops/download-db-acquia.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "lagoon" ]; then
  echo "==> Started database dump download from Lagoon."
  export DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT="${DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT:-${LAGOON_PROJECT}}"
  export DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE:-${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}}"
  export DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT:-${DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT}}"
  ./scripts/drevops/download-db-lagoon.sh
fi

if [ "${DREVOPS_DB_DOWNLOAD_SOURCE}" = "docker_registry" ]; then
  echo "==> Started database dump download from Docker Registry."
  export DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE="${DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE:-${DREVOPS_DB_DOCKER_IMAGE}}"
  export DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME:-${DREVOPS_DOCKER_REGISTRY_USERNAME}}"
  export DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN:-${DREVOPS_DOCKER_REGISTRY_TOKEN}}"
  export DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY:-${DREVOPS_DOCKER_REGISTRY:-docker.io}}"
  ./scripts/drevops/download-db-image.sh
fi

echo "==> Downloaded database dump file in ${DREVOPS_DB_DIR}."
ls -Alh "${DREVOPS_DB_DIR}"

if [ -n "${DREVOPS_DB_DOWNLOAD_POST_PROCESS}" ]; then
  echo "==> Running database post download processing command(s) '${DREVOPS_DB_DOWNLOAD_POST_PROCESS}'."
  eval "${DREVOPS_DB_DOWNLOAD_POST_PROCESS}"
fi
