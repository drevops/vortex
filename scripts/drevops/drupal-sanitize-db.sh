#!/usr/bin/env bash
##
# Sanitize database.
#
# shellcheck disable=SC2086

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Database sanitized account email replacement.
DREVOPS_DRUPAL_DB_SANITIZE_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL:-user+%uid@localhost}"

# Database sanitized account password replacement.
DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD:-${RANDOM}${RANDOM}${RANDOM}${RANDOM}}"

# Replace username with mail.
DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL="${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL:-0}"

# Path to file with custom sanitization SQL queries.
#
# To skip custom sanitization, remove the #DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE
# file from the codebase.
DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE:-${DREVOPS_APP}/scripts/sanitize.sql}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Sanitizing database."

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

drush_opts=(-y)
[ -z "${DREVOPS_DEBUG:-}" ] && drush_opts+=(-q)

# Always sanitize password and email using standard methods.
$drush "${drush_opts[@]}" sql:sanitize --sanitize-password="${DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD}" --sanitize-email="${DREVOPS_DRUPAL_DB_SANITIZE_EMAIL}"
pass "Sanitized database using drush sql:sanitize."

if [ "${DREVOPS_DRUPAL_DB_SANITIZE_REPLACE_USERNAME_WITH_EMAIL:-}" = "1" ]; then
  $drush sql:query "UPDATE \`users_field_data\` set users_field_data.name=users_field_data.mail WHERE uid <> '0';"
  pass "Updated username with user email."
fi

# Sanitize using additional SQL commands provided in file.
if [ -f "${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE:-}" ]; then
  $drush "${drush_opts[@]}" sql:query --file="${DREVOPS_DRUPAL_DB_SANITIZE_ADDITIONAL_FILE}"
  pass "Applied custom sanitization commands."
fi

# User mail and name for use 0 could have been sanitized - resetting it.
$drush "${drush_opts[@]}" sql:query "UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
$drush "${drush_opts[@]}" sql:query "UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
pass "Reset user 0 username and email."

# User email could have been sanitized - setting it back to a pre-defined email.
if [ -n "${DREVOPS_DRUPAL_ADMIN_EMAIL:-}" ]; then
  $drush "${drush_opts[@]}" sql:query "UPDATE \`users_field_data\` SET mail = '${DREVOPS_DRUPAL_ADMIN_EMAIL:-}' WHERE uid = '1';"
  pass "Updated user 1 email."
fi

echo
