#!/usr/bin/env bash
##
# Login to a Drupal site as an admin user.
#
# shellcheck disable=SC2086

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Flag to block or unblock admin.
DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

if [ "${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-}" = "1" ]; then
  if $drush pm:list --status=enabled | grep -q user_expire; then
    $drush sql:query 'UPDATE `user__field_password_expiration` SET `field_password_expiration_value` = 0 WHERE `bundle` = "user" AND `entity_id` = 1;'
  fi
  $drush sql:query "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs $drush -- user:unblock
fi

$drush user:login
