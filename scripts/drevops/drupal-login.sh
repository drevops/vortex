#!/usr/bin/env bash
##
# Login to a Drupal site.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# Flag to unblock admin.
DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${DREVOPS_DRUPAL_UNBLOCK_ADMIN}" = "1" ]; then
  echo "==> Unblocking admin user."
  $drush sqlq "UPDATE \`user__field_password_expiration\` SET \`field_password_expiration_value\` = 0 WHERE \`bundle\` = \"user\" AND \`entity_id\` = 1;" || true
  $drush sqlq "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs $drush -- uublk
fi

$drush uli
