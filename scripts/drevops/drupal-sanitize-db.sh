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

# Database sanitized account email replacement.
DB_SANITIZE_EMAIL="${DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DB_SANITIZE_PASSWORD="${DB_SANITIZE_PASSWORD:-password}"

# Replace username with mail.
DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL="${DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL:-0}"

# Path to file with custom sanitization SQL queries.
# To skip custom sanitization, remove the DB_SANITIZE_FILE file from the codebase.
DB_SANITIZE_FILE="${DB_SANITIZE_FILE:-${APP}/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${SKIP_DB_SANITIZE}" = "1" ]; then
  echo "==> Skipped database sanitization." && exit 0
fi

echo "==> Started database sanitization."

echo "  > Sanitizing database using drush sql-sanitize."
# Always sanitize password and email using standard methods.
$drush ${DRUSH_ALIAS} sql-sanitize --sanitize-password="${DB_SANITIZE_PASSWORD}" --sanitize-email="${DB_SANITIZE_EMAIL}" -y

if [ "${DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL}" = "1" ]; then
  echo "  > Updating username with user email."
  $drush ${DRUSH_ALIAS} sql-query "UPDATE \`users_field_data\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';"
fi

# Sanitize using additional SQL commands provided in file.
# To skip custom sanitization, remove the DB_SANITIZE_FILE file from the codebase.
if [ -f "${DB_SANITIZE_FILE}" ]; then
  echo "  > Applying custom sanitization commands from file ${DB_SANITIZE_FILE}."
  $drush ${DRUSH_ALIAS} sql-query --file="${DB_SANITIZE_FILE}"
fi

echo "==> Finished database sanitization."
