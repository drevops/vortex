#!/usr/bin/env bash
##
# Import and sanitize database.
#

set -e

# Path to the database dump file.
DB_DUMP="${DB_DUMP:-/tmp/data/db.sql}"

# Flag to use database import progress indicator (pv).
DB_IMPORT_PROGRESS="${DB_IMPORT_PROGRESS:-1}"

# Flag to skip sanitization of the database.
SKIP_SANITIZE_DB="${SKIP_SANITIZE_DB:-}"

# Database sanitized account email replacement.
DB_SANITIZE_EMAIL="${DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DB_SANITIZE_PASSWORD="${DB_SANITIZE_PASSWORD:-password}"

# Path to file with custom sanitization SQL queries.
DB_SANITIZE_SQL="${DB_SANITIZE_SQL:-/app/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

[ ! -f "${DB_DUMP}" ] && echo "ERROR: Database dump $DB_DUMP not found" && exit 1

echo "==> Removing existing database tables"
drush sql-drop -y

echo "==> Importing database"
if [ "$DB_IMPORT_PROGRESS" -eq 1 ]; then
  pv "${DB_DUMP}" | drush sql-cli
else
  drush sqlc < "${DB_DUMP}"
fi

if [ -z "${SKIP_SANITIZE_DB}" ]; then
  # Sanitize password and email using standard methods.
  drush sql-sanitize --sanitize-password="${DB_SANITIZE_PASSWORD}" --sanitize-email="${DB_SANITIZE_EMAIL}" -y
  # Sanitise using an additional SQL commands provided in file.
  if [ -f "${DB_SANITIZE_SQL}" ]; then
    echo "==> Applying custom sanitization commands"
    drush sql-query --file="${DB_SANITIZE_SQL}"
  fi
else
  echo "==> Skipping sanitization"
fi
