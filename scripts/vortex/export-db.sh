#!/usr/bin/env bash
##
# Export database.
#
# This is a router script to call relevant scripts based on type.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Name of the database container image to use. Uncomment to use an image with
# a DB data loaded into it.
# @see https://github.com/drevops/mariadb-drupal-data to seed your DB image.
VORTEX_EXPORT_DB_IMAGE="${VORTEX_EXPORT_DB_IMAGE:-${VORTEX_DB_IMAGE:-}}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database export."

if [ -z "${VORTEX_EXPORT_DB_IMAGE}" ]; then
  # Export database as a file.
  docker compose exec -T cli ./scripts/vortex/export-db-file.sh "$@"
else
  # Export database as a container image.
  VORTEX_EXPORT_DB_IMAGE="${VORTEX_EXPORT_DB_IMAGE}" ./scripts/vortex/export-db-image.sh "$@"

  # Deploy container image.
  # @todo Move deployment into a separate script.
  if [ "${VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED:-}" = "1" ]; then
    VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP=database=${VORTEX_EXPORT_DB_IMAGE} \
      ./scripts/vortex/deploy-container-registry.sh
  fi
fi

pass "Finished database export."
