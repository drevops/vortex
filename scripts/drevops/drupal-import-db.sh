#!/usr/bin/env bash
##
# Import database.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Flag to use database import progress indicator (pv).
DREVOPS_DB_IMPORT_PROGRESS="${DREVOPS_DB_IMPORT_PROGRESS:-1}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# ------------------------------------------------------------------------------

# Use local or global Drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

[ ! -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ] && echo "ERROR: Database dump ${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE} not found." && exit 1

echo "==> Removing existing database tables."
$drush sql-drop -y

echo "==> Importing database."
if [ "${DREVOPS_DB_IMPORT_PROGRESS}" -eq 1 ]; then
  pv "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" | $drush sql-cli
else
  $drush sqlc < "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
fi
