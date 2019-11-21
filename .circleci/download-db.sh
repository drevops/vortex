#!/usr/bin/env bash
##
# Download DB in CI.
#
# Supports reading database from and storing to cache.
#

set -e

# Flag to force DB download even if the cache exists.
# Usually in CircleCI UI to override per build cache.
FORCE_DB_DOWNLOAD="${FORCE_DB_DOWNLOAD:-}"

# Data directory location.
DATADIR="${DATADIR:-.data}"

# Pattern of the DB dump file.
DB_DUMP_PATTERN="${DB_DUMP_PATTERN:-db*.sql}"

# Semaphore file used to check if the DB has been previously downloaded.
DB_SEMAPHORE_FILE="${DB_SEMAPHORE_FILE:-/tmp/db-new}"

# ------------------------------------------------------------------------------

# Download database only if it has not been restored from the cache OR
# if the $FORCE_DB_DOWNLOAD flag is set.
[ "${FORCE_DB_DOWNLOAD}" != "" ] && echo "==> Forced DB download flag FORCE_DB_DOWNLOAD is set"

# Remove any previously set semaphore files.
rm -f "${DB_SEMAPHORE_FILE}" >/dev/null

[ -d "${DATADIR}" ] && found_db=$(find "${DATADIR}" -name "${DB_DUMP_PATTERN}")

if [ "${found_db}" == ""  ] || [ "${FORCE_DB_DOWNLOAD}" != "" ]; then
  echo "==> Created DB semaphore file ${DB_SEMAPHORE_FILE}"
  touch "${DB_SEMAPHORE_FILE}"
  ahoy download-db || exit 1
  export DOCTOR_CHECK_DB=1
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  # Enforce DB sanitization during the build.
  export SKIP_SANITIZE_DB=0
  # Do not run post DB-import scripts to work with unmodified database.
  export SKIP_POST_DB_IMPORT=1
  .circleci/build.sh;
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  echo "==> Exporting built DB for caching"
  ahoy export-db db.sql;
fi
