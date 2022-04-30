#!/usr/bin/env bash
##
# Login to a Drupal site.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Path to the DOCROOT.
WEBROOT="${WEBROOT:-docroot}"

# Drush alias.
DRUSH_ALIAS="${DRUSH_ALIAS:-}"

# Flag to unblock admin.
DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${DREVOPS_DRUPAL_UNBLOCK_ADMIN}" = "1" ]; then
  echo "==> Unblocking admin user."
  $drush ${DRUSH_ALIAS} sqlq "UPDATE \`user__field_password_expiration\` SET \`field_password_expiration_value\` = 0 WHERE \`bundle\` = \"user\" AND \`entity_id\` = 1;" || true
  $drush ${DRUSH_ALIAS} sqlq "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs $drush -- uublk
fi

$drush ${DRUSH_ALIAS} uli
