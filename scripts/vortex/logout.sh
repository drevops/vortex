#!/usr/bin/env bash
##
# Log out an admin from a Drupal site.
#
# shellcheck disable=SC1090,SC1091,SC2086,SC2032,SC2033

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Flag to block or unblock admin.
DRUPAL_UNBLOCK_ADMIN="${DRUPAL_UNBLOCK_ADMIN:-1}"

# ------------------------------------------------------------------------------

drush() { ./vendor/bin/drush -y "$@"; }

if [ "${DRUPAL_UNBLOCK_ADMIN:-}" = "1" ]; then
  drush sql:query "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs drush -- user:block
fi
