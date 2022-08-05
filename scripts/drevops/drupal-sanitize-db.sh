#!/usr/bin/env bash
##
# Sanitize database.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Database sanitized account email replacement.
DREVOPS_DRUPAL_DB_SANITIZE_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD:-${RANDOM}${RANDOM}${RANDOM}${RANDOM}}"

# Replace username with mail.
DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL:-0}"

# Path to file with custom sanitization SQL queries.
# To skip custom sanitization, remove the DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE file from the codebase.
DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE:-${DREVOPS_APP}/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

drush_opts=(-y)
[ -z "${DREVOPS_DEBUG}" ] && drush_opts+=(-q)

echo "🤖 Sanitizing database."

# Always sanitize password and email using standard methods.
$drush "${drush_opts[@]}" sql-sanitize --sanitize-password="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD}" --sanitize-email="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL}"
echo "   ✅  Sanitized database using drush sql-sanitize."

if [ "${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL}" = "1" ]; then
  $drush sql-query "UPDATE \`users_field_data\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';"
  echo "   ✅  Updated username with user email."
fi

# Sanitize using additional SQL commands provided in file.
# To skip custom sanitization, remove the DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE file from the codebase.
if [ -f "${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}" ]; then
  $drush "${drush_opts[@]}" sql-query --file="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}"
  echo "   ✅  Applied custom sanitization commands."
fi

# User mail and name for use 0 could have been sanitized - resetting it.
$drush "${drush_opts[@]}" sql-query "UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
$drush "${drush_opts[@]}" sql-query "UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
echo "   ✅  Reset user 0 username and email."

# User email could have been sanitized - setting it back to a pre-defined email.
if [ -n "${DREVOPS_DRUPAL_ADMIN_EMAIL}" ]; then
  $drush "${drush_opts[@]}" sql-query "UPDATE \`users_field_data\` SET mail = '${DREVOPS_DRUPAL_ADMIN_EMAIL}' WHERE uid = '1';"
  echo "   ✅  Updated user 1 email."
fi
