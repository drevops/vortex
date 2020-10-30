#!/usr/bin/env bash
##
# Import database.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

# Flag to use database import progress indicator (pv).
DB_IMPORT_PROGRESS="${DB_IMPORT_PROGRESS:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

[ ! -f "${DB_DIR}/${DB_FILE}" ] && echo "ERROR: Database dump ${DB_DIR}/${DB_FILE} not found." && exit 1

echo "==> Removing existing database tables."
$drush sql-drop -y

echo "==> Importing database."
if [ "${DB_IMPORT_PROGRESS}" -eq 1 ]; then
  pv "${DB_DIR}/${DB_FILE}" | $drush sql-cli
else
  $drush sqlc < "${DB_DIR}/${DB_FILE}"
fi
