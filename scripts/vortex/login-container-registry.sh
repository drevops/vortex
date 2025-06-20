#!/usr/bin/env bash
##
# Login to the container registry.
#
# Supported registries:
# - docker.io
#
# IMPORTANT! This script runs outside the container on the host system.
#
# @todo Add support for more registries.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
VORTEX_CONTAINER_REGISTRY="${VORTEX_CONTAINER_REGISTRY:-docker.io}"

# The username to login into the container registry.
#
# If not provided, the script will skip the login step.
VORTEX_CONTAINER_REGISTRY_USER="${VORTEX_CONTAINER_REGISTRY_USER:-}"

# The password to login into the container registry.
#
# If not provided, the script will skip the login step.
VORTEX_CONTAINER_REGISTRY_PASS="${VORTEX_CONTAINER_REGISTRY_PASS:-}"

# Path to Docker configuration directory.
DOCKER_CONFIG="${DOCKER_CONFIG:-${HOME}/.docker}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "$(echo "${VORTEX_CONTAINER_REGISTRY}" | xargs)" ] && fail "VORTEX_CONTAINER_REGISTRY should not be empty." && exit 1

if [ -f "${DOCKER_CONFIG}" ] && grep -q "${VORTEX_CONTAINER_REGISTRY}" "${DOCKER_CONFIG}/config.json"; then
  note "Already logged in to the registry \"${VORTEX_CONTAINER_REGISTRY}\"."
elif [ -n "${VORTEX_CONTAINER_REGISTRY_USER}" ] && [ -n "${VORTEX_CONTAINER_REGISTRY_PASS}" ]; then
  task "Logging in to registry \"${VORTEX_CONTAINER_REGISTRY}\"."
  echo "${VORTEX_CONTAINER_REGISTRY_PASS}" | docker login --username "${VORTEX_CONTAINER_REGISTRY_USER}" --password-stdin "${VORTEX_CONTAINER_REGISTRY}"
else
  note "Skipping login to the container registry as either VORTEX_CONTAINER_REGISTRY_USER or VORTEX_CONTAINER_REGISTRY_PASS was not provided."
fi
