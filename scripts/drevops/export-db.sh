#!/usr/bin/env bash
##
# Export database.
#
# This is a router script to call relevant scripts based on type.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Name of the database container image to use. Uncomment to use an image with
# a DB data loaded into it.
# @see https://github.com/drevops/mariadb-drupal-data to seed your DB image.
DREVOPS_DB_IMAGE="${DREVOPS_DB_IMAGE:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database export."

if [ -z "${DREVOPS_DB_IMAGE}" ]; then
  # Export database as a file.
  docker compose exec -T cli ./scripts/drevops/export-db-file.sh "$@"
else
  # Export database as a container image.
  DREVOPS_DB_EXPORT_IMAGE="${DREVOPS_DB_IMAGE}" ./scripts/drevops/export-db-image.sh "$@"

  # Deploy container image.
  # @todo Move deployment into a separate script.
  if [ "${DREVOPS_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED:-}" = "1" ]; then
    DREVOPS_DEPLOY_CONTAINER_REGISTRY_MAP=mariadb=${DREVOPS_DB_IMAGE} \
      ./scripts/drevops/deploy-container-registry.sh
  fi
fi

pass "Finished database export."
