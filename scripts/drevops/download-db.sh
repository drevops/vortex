#!/usr/bin/env bash
##
# Download database dump.
#
# Download is supported from FTP, CURL or Acquia Cloud.
#
# This is a router script to call relevant deployment scripts based on type.
#
# For required variables based on the deployment type,
# see ./scripts/drevops/download-db-[type].sh file.

set -e

# The type of database dump download. Can be one of: ftp, curl, acquia.
# Defaulting to CURL to allow use demo DB.
DOWNLOAD_DB_TYPE="${DOWNLOAD_DB_TYPE:-curl}"

# ------------------------------------------------------------------------------

[ -z "${DOWNLOAD_DB_TYPE}" ] && echo "Missing required value for DOWNLOAD_DB_TYPE. Must be one of: ftp, curl, acquia." && exit 1

if [ "${DOWNLOAD_DB_TYPE}" == "ftp" ]; then
  echo "==> Starting database dump download from FTP"
  ./scripts/drevops/download-db-ftp.sh
fi

if [ "${DOWNLOAD_DB_TYPE}" == "curl" ]; then
  echo "==> Starting database dump download from CURL"
  ./scripts/drevops/download-db-curl.sh
fi

if [ "${DOWNLOAD_DB_TYPE}" == "acquia" ]; then
  echo "==> Starting database dump download from Acquia"
  ./scripts/drevops/download-db-acquia.sh
fi
