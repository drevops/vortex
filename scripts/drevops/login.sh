#!/usr/bin/env bash
##
# Login to a Drupal site as an admin user.
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

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

# Wrapper around Drush to make it easier to read Drush commands.
drush() {
  local drush_local="${DREVOPS_APP}/vendor/bin/drush"
  [ ! -f "${drush_local}" ] && fail "Drush not found at ${drush_local}." && exit 1
  "${drush_local}" -y "$@"
}

if [ "${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-}" = "1" ]; then
  if drush pm:list --status=enabled | grep -q user_expire; then
    drush sql:query 'UPDATE `user__field_password_expiration` SET `field_password_expiration_value` = 0 WHERE `bundle` = "user" AND `entity_id` = 1;'
  fi
  drush sql:query "SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1';" | head -n 1 | xargs drush -- user:unblock
fi

drush user:login
