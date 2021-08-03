#!/usr/bin/env bash
##
# Sanitize database.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip DB sanitization.
SKIP_DB_SANITIZE="${SKIP_DB_SANITIZE:-}"

# Path to the application.
APP="${APP:-/app}"

# Drush alias.
DRUSH_ALIAS="${DRUSH_ALIAS:-}"

# Flag to use database import progress indicator (pv).
DB_IMPORT_PROGRESS="${DB_IMPORT_PROGRESS:-1}"

# Database sanitized account email replacement.
DB_SANITIZE_EMAIL="${DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DB_SANITIZE_PASSWORD="${DB_SANITIZE_PASSWORD:-password}"

# Path to file with custom sanitization SQL queries.
# To skip custom sanitization, remove the DB_SANITIZE_FILE file from the codebase.
DB_SANITIZE_FILE="${DB_SANITIZE_FILE:-${APP}/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ -z "${SKIP_DB_SANITIZE}" ]; then
  # Always sanitize password and email using standard methods.
  $drush ${DRUSH_ALIAS} sql-sanitize --sanitize-password="${DB_SANITIZE_PASSWORD}" --sanitize-email="${DB_SANITIZE_EMAIL}" -y

  # Sanitize using additional SQL commands provided in file.
  # To skip custom sanitization, remove the DB_SANITIZE_FILE file from the codebase.
  if [ -f "${DB_SANITIZE_FILE}" ]; then
    echo "==> Applying custom sanitization commands from file ${DB_SANITIZE_FILE}."
    $drush ${DRUSH_ALIAS} sql-query --file="${DB_SANITIZE_FILE}"
  fi
fi
