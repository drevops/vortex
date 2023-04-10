#!/usr/bin/env bash
##
# Login to Docker container registry.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The username of the docker registry.
DREVOPS_DOCKER_REGISTRY_USERNAME="${DREVOPS_DOCKER_REGISTRY_USERNAME:-}"

# The token of the docker registry.
DREVOPS_DOCKER_REGISTRY_TOKEN="${DREVOPS_DOCKER_REGISTRY_TOKEN:-}"

# Docker registry name. Provide port, if required as <server_name>:<port>.
DREVOPS_DOCKER_REGISTRY="${DREVOPS_DOCKER_REGISTRY:-docker.io}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m  [OK] %s\033[0m\n" "$1" || printf "  [OK] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

# Check all required values.
[ -z "${DREVOPS_DOCKER_REGISTRY}" ] && echo "Missing required value for DREVOPS_DOCKER_REGISTRY." && exit 1

if [ -f "$HOME/.docker/config.json" ] && grep -q "${DREVOPS_DOCKER_REGISTRY}" "$HOME/.docker/config.json"; then
  note "Already logged in to registry \"${DREVOPS_DOCKER_REGISTRY}\"."
elif [ -n "${DREVOPS_DOCKER_REGISTRY_USERNAME}" ] &&  [ -n "${DREVOPS_DOCKER_REGISTRY_TOKEN}" ]; then
  note "Logging in to registry \"${DREVOPS_DOCKER_REGISTRY}\"."
  echo "${DREVOPS_DOCKER_REGISTRY_TOKEN}" | docker login --username "${DREVOPS_DOCKER_REGISTRY_USERNAME}" --password-stdin "${DREVOPS_DOCKER_REGISTRY}"
else
  note "Skipping login into Docker registry as either DREVOPS_DOCKER_REGISTRY_USERNAME or DREVOPS_DOCKER_REGISTRY_TOKEN was not provided."
fi
