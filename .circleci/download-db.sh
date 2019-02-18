#!/usr/bin/env bash
##
# Download DB in CI.
#
set -e

DATADIR=${DATADIR:-.data}
DB_FILE=${DB_FILE:-db.sql}
DB_SEMAPHORE_FILE=${DB_SEMAPHORE_FILE:-/tmp/db-new}

# Download database only if it has not been restored from the cache OR
# if the $FORCE_DB_DOWNLOAD flag is set (usually in CircleCI UI).
FORCE_DB_DOWNLOAD=${FORCE_DB_DOWNLOAD:-}

if [ ! -f "${DATADIR}"/"${DB_FILE}" ] || [ "${FORCE_DB_DOWNLOAD}" != "" ]; then
  echo "==> Created DB semaphore file ${DB_SEMAPHORE_FILE}"
  touch "${DB_SEMAPHORE_FILE}"

  ahoy download-db;
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  .circleci/build.sh;
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  echo "==> Exporting built DB for caching"
  ahoy export-db db.sql;
fi
