#!/usr/bin/env bash
##
# Import database.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# Directory with database dump file.
DREVOPS_DB_IMPORT_FILE_DIR="${DREVOPS_DB_IMPORT_FILE_DIR:-${DREVOPS_DB_DIR}}"

# Database dump file name.
DREVOPS_DB_IMPORT_FILE_NAME="${DREVOPS_DB_IMPORT_FILE_NAME:-${DREVOPS_DB_FILE}}"

# Flag to use database import progress indicator (pv).
DREVOPS_DB_IMPORT_FILE_PROGRESS="${DREVOPS_DB_IMPORT_FILE_PROGRESS:-1}"

# ------------------------------------------------------------------------------

echo "==> Started DB import from file."

# Use local or global Drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

[ ! -f "${DREVOPS_DB_IMPORT_FILE_DIR}/${DREVOPS_DB_IMPORT_FILE_NAME}" ] && echo "ERROR: Database dump ${DREVOPS_DB_IMPORT_FILE_DIR}/${DREVOPS_DB_IMPORT_FILE_NAME} not found." && exit 1

echo "  > Removing existing database tables."
$drush -q sql-drop -y

echo "  > Importing database."
if [ "${DREVOPS_DB_IMPORT_FILE_PROGRESS}" -eq 1 ] && command -v composer > /dev/null; then
  pv "${DREVOPS_DB_IMPORT_FILE_DIR}/${DREVOPS_DB_IMPORT_FILE_NAME}" | $drush sql-cli
else
  $drush sqlc < "${DREVOPS_DB_IMPORT_FILE_DIR}/${DREVOPS_DB_IMPORT_FILE_NAME}"
fi

echo "==> Finished DB import from file."
