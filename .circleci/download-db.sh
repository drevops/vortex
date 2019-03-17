#!/usr/bin/env bash
##
# Download DB in CI.
#
set -e

DATADIR=${DATADIR:-.data}
DB_FILE=${DB_FILE:-db_*.sql}
DB_SEMAPHORE_FILE=${DB_SEMAPHORE_FILE:-/tmp/db-new}

# Download database only if it has not been restored from the cache OR
# if the $FORCE_DB_DOWNLOAD flag is set (usually in CircleCI UI).
FORCE_DB_DOWNLOAD=${FORCE_DB_DOWNLOAD:-}

# Remove any previously set semaphore files.
rm -f "${DB_SEMAPHORE_FILE}" >/dev/null

[ -d "${DATADIR}" ] && found_db=$(find "${DATADIR}" -name "${DB_FILE}")
if [ "${found_db}" == ""  ] || [ "${FORCE_DB_DOWNLOAD}" != "" ]; then
  echo "==> Created DB semaphore file ${DB_SEMAPHORE_FILE}"
  touch "${DB_SEMAPHORE_FILE}"

  ahoy download-db || exit 1
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  # Enforce DB sanitization during the build.
  export SKIP_SANITIZE_DB=0
  # Do not run post DB-import scripts to work with unmodified database.
  export SKIP_POST_IMPORT=1
  .circleci/build.sh;
fi

if [ -f "${DB_SEMAPHORE_FILE}" ]; then
  echo "==> Exporting built DB for caching"
  ahoy export-db db.sql;
fi
