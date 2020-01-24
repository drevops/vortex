#!/usr/bin/env bash
##
# Import and sanitize database.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

# Flag to use database import progress indicator (pv).
DB_IMPORT_PROGRESS="${DB_IMPORT_PROGRESS:-1}"

# Database sanitized account email replacement.
DB_SANITIZE_EMAIL="${DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DB_SANITIZE_PASSWORD="${DB_SANITIZE_PASSWORD:-password}"

# Path to file with custom sanitization SQL queries.
DB_SANITIZE_FILE="${DB_SANITIZE_FILE:-/app/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

[ ! -f "${DB_DIR}/${DB_FILE}" ] && echo "ERROR: Database dump ${DB_DIR}/${DB_FILE} not found" && exit 1

echo "==> Removing existing database tables"
drush sql-drop -y

echo "==> Importing database"
if [ "${DB_IMPORT_PROGRESS}" -eq 1 ]; then
  pv "${DB_DIR}/${DB_FILE}" | drush sql-cli
else
  drush sqlc < "${DB_DIR}/${DB_FILE}"
fi

# Always sanitize password and email using standard methods.
drush sql-sanitize --sanitize-password="${DB_SANITIZE_PASSWORD}" --sanitize-email="${DB_SANITIZE_EMAIL}" -y

# Sanitize using additional SQL commands provided in file.
# To skip custom sanitization, remove the DB_SANITIZE_FILE file from the codebase.
if [ -f "${DB_SANITIZE_FILE}" ]; then
  echo "==> Applying custom sanitization commands from file ${DB_SANITIZE_FILE}"
  drush sql-query --file="${DB_SANITIZE_FILE}"
fi
