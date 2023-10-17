#!/usr/bin/env bash
##
# Login to the Docker container registry.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The username of the docker registry.
DOCKER_USER="${DOCKER_USER:-}"

# The token of the docker registry.
DOCKER_PASS="${DOCKER_PASS:-}"

# Docker registry name.
#
# Provide port, if required as `<server_name>:<port>`.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

# Check all required values.
[ -z "${DOCKER_REGISTRY}" ] && echo "Missing required value for DOCKER_REGISTRY." && exit 1

if [ -f "${HOME}/.docker/config.json" ] && grep -q "${DOCKER_REGISTRY}" "${HOME}/.docker/config.json"; then
  note "Already logged in to registry \"${DOCKER_REGISTRY}\"."
elif [ -n "${DOCKER_USER}" ] && [ -n "${DOCKER_PASS}" ]; then
  note "Logging in to registry \"${DOCKER_REGISTRY}\"."
  echo "${DOCKER_PASS}" | docker login --username "${DOCKER_USER}" --password-stdin "${DOCKER_REGISTRY}"
else
  note "Skipping login into Docker registry as either DOCKER_USER or DOCKER_PASS was not provided."
fi
