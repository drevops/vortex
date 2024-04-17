#!/usr/bin/env bash
##
# Deploy via pushing Docker images to Docker registry.
#
# Push multiple docker images by tagging services provided in the
# $DREVOPS_DEPLOY_DOCKER_MAP variable.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Comma-separated map of docker services and images to use for deployment in
# format "service1=org/image1,service2=org/image2".
DREVOPS_DEPLOY_DOCKER_MAP="${DREVOPS_DEPLOY_DOCKER_MAP:-}"

# The username for the docker registry.
DOCKER_USER="${DOCKER_USER:-}"

# The password for the docker registry.
DOCKER_PASS="${DOCKER_PASS:-}"

# Docker registry name. Provide port, if required as <server_name>:<port>.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

# The tag of the image to push to.
DREVOPS_DOCKER_IMAGE_TAG="${DREVOPS_DOCKER_IMAGE_TAG:-latest}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in docker; do command -v ${cmd} >/dev/null || { fail "Command ${cmd} is not available"; exit 1; }; done

info "Started DOCKER deployment."

# Only deploy if the map was provided, but do not fail if it has not as this
# may be called as a part of another task.
# @todo: Handle this better - empty $DREVOPS_DEPLOY_DOCKER_MAP should use defaults.
[ -z "${DREVOPS_DEPLOY_DOCKER_MAP}" ] && echo "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable. Docker deployment will not continue." && exit 0

services=()
images=()
# Parse and validate map.
IFS=',' read -r -a values <<<"${DREVOPS_DEPLOY_DOCKER_MAP}"
for value in "${values[@]}"; do
  IFS='=' read -r -a parts <<<"${value}"
  [ "${#parts[@]}" -ne 2 ] && fail "invalid key/value pair \"${value}\" provided." && exit 1
  services+=("${parts[0]}")
  images+=("${parts[1]}")
done

# Login to the registry.
export DOCKER_USER="${DOCKER_USER}"
export DOCKER_PASS="${DOCKER_PASS}"
export DOCKER_REGISTRY="${DOCKER_REGISTRY}"
./scripts/drevops/login-docker.sh

for key in "${!services[@]}"; do
  service="${services[${key}]}"
  image="${images[${key}]}"

  note "Processing service ${service}."
  # Check if the service is running.)
  cid=$(docker compose ps -q "${service}")

  [ -z "${cid}" ] && fail "Service \"${service}\" is not running." && exit 1
  note "Found \"${service}\" service container with id \"${cid}\"."

  [ -n "${image##*:*}" ] && image="${image}:${DREVOPS_DOCKER_IMAGE_TAG}"
  new_image="${DOCKER_REGISTRY}/${image}"

  note "Committing Docker image with name \"${new_image}\"."
  iid=$(docker commit "${cid}" "${new_image}")
  iid="${iid#sha256:}"
  note "Committed Docker image with id \"${iid}\"."

  note "Pushing Docker image to the registry."
  docker push "${new_image}"
done

pass "Finished DOCKER deployment."
