#!/usr/bin/env bash
##
# Log out an admin from a Drupal site.
#
# shellcheck disable=SC2086,SC2032,SC2033

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Flag to block or unblock admin.
DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
# Wrapper around Drush to make it easier to read Drush commands.
drush() {
  local drush_local="${DREVOPS_APP}/vendor/bin/drush"
  [ ! -f "${drush_local}" ] && fail "Drush not found at ${drush_local}." && exit 1
  "${drush_local}" -y "$@"
}

if [ "${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-}" = "1" ]; then
  drush sql:query "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs drush -- user:block
fi
