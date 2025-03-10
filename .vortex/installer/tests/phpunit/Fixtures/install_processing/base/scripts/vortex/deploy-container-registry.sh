#!/usr/bin/env bash
##
# Deploy via pushing container images to the container registry.
#
# Push multiple container images by tagging services provided in the
# $VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Comma-separated map of container services and images to use for deployment in
# format "service1=org/image1,service2=org/image2".
VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="${VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP:-}"

# The tag of the container image.
VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG="${VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG:-latest}"

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
VORTEX_DEPLOY_CONTAINER_REGISTRY="${VORTEX_DEPLOY_CONTAINER_REGISTRY:-${VORTEX_CONTAINER_REGISTRY:-docker.io}}"

# The username to login into the container registry.
VORTEX_DEPLOY_CONTAINER_REGISTRY_USER="${VORTEX_DEPLOY_CONTAINER_REGISTRY_USER:-${VORTEX_CONTAINER_REGISTRY_USER:-}}"

# The password to login into the container registry.
VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS="${VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS:-${VORTEX_CONTAINER_REGISTRY_PASS:-}}"

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

info "Started container registry deployment."

[ -z "${VORTEX_DEPLOY_CONTAINER_REGISTRY}" ] && fail "Missing required value for VORTEX_DEPLOY_CONTAINER_REGISTRY." && exit 1
[ -z "${VORTEX_DEPLOY_CONTAINER_REGISTRY_USER}" ] && fail "Missing required value for VORTEX_DEPLOY_CONTAINER_REGISTRY_USER." && exit 1
[ -z "${VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS}" ] && fail "Missing required value for VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS." && exit 1

# Only deploy if the map was provided, but do not fail if it has not as this
# may be called as a part of another task.
# @todo: Handle this better - empty $VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP should use defaults.
[ -z "${VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP}" ] && echo "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable. Container registry deployment will not continue." && exit 0

services=()
images=()
# Parse and validate map.
IFS=',' read -r -a values <<<"${VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP}"
for value in "${values[@]}"; do
  IFS='=' read -r -a parts <<<"${value}"
  [ "${#parts[@]}" -ne 2 ] && fail "invalid key/value pair \"${value}\" provided." && exit 1
  services+=("${parts[0]}")
  images+=("${parts[1]}")
done

export VORTEX_CONTAINER_REGISTRY="${VORTEX_DEPLOY_CONTAINER_REGISTRY}"
export VORTEX_CONTAINER_REGISTRY_USER="${VORTEX_DEPLOY_CONTAINER_REGISTRY_USER}"
export VORTEX_CONTAINER_REGISTRY_PASS="${VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS}"
./scripts/vortex/login-container-registry.sh

for key in "${!services[@]}"; do
  service="${services[${key}]}"
  image="${images[${key}]}"

  note "Processing service ${service}."
  # Check if the service is running.
  cid=$(docker compose ps -q "${service}")

  [ -z "${cid}" ] && fail "Service \"${service}\" is not running." && exit 1
  note "Found \"${service}\" service container with id \"${cid}\"."

  [ -n "${image##*:*}" ] && image="${image}:${VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG}"
  new_image="${VORTEX_DEPLOY_CONTAINER_REGISTRY}/${image}"

  note "Committing container image with name \"${new_image}\"."
  iid=$(docker commit "${cid}" "${new_image}")
  iid="${iid#sha256:}"
  note "Committed container image with id \"${iid}\"."

  note "Pushing container image to the registry."
  docker push "${new_image}"
done

pass "Finished container registry deployment."
