#!/usr/bin/env bash
##
# Log out an admin from a Drupal site.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Flag to block or unblock admin.
DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN}" = "1" ]; then
  $drush sqlq "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs $drush -q -- ublk
fi
