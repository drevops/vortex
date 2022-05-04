#!/usr/bin/env bash
##
# Sanitize database.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip DB sanitization.
DREVOPS_DRUPAL_DB_SANITIZE_SKIP="${DREVOPS_DRUPAL_DB_SANITIZE_SKIP:-}"

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# Database sanitized account email replacement.
DREVOPS_DRUPAL_DB_SANITIZE_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD:-${RANDOM}${RANDOM}${RANDOM}${RANDOM}}"

# Replace username with mail.
DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL:-0}"

# Path to file with custom sanitization SQL queries.
# To skip custom sanitization, remove the DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE file from the codebase.
DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE:-${DREVOPS_APP}/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${DREVOPS_DRUPAL_DB_SANITIZE_SKIP}" = "1" ]; then
  echo "==> Skipped database sanitization." && exit 0
fi

echo "==> Started database sanitization."

echo "  > Sanitizing database using drush sql-sanitize."
# Always sanitize password and email using standard methods.
$drush sql-sanitize --sanitize-password="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD}" --sanitize-email="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL}" -y

if [ "${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_FROM_EMAIL}" = "1" ]; then
  echo "  > Updating username with user email."
  $drush sql-query "UPDATE \`users_field_data\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';"
fi

# Sanitize using additional SQL commands provided in file.
# To skip custom sanitization, remove the DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE file from the codebase.
if [ -f "${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}" ]; then
  echo "  > Applying custom sanitization commands from file ${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}."
  $drush sql-query --file="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}"
fi

echo "==> Finished database sanitization."
