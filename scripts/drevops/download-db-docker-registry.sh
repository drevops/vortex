#!/usr/bin/env bash
##
# Download DB dump from docker image.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091,SC2015

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The Docker image containing database passed in a form of `<org>/<repository>`.
DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE:-}"

# The username of the docker registry to download the database from.
DOCKER_USER="${DOCKER_USER:-}"

# The token of the docker registry to download the database from.
DOCKER_PASS="${DOCKER_PASS:-}"

# Docker registry name.
# Provide port, if required as `<server_name>:<port>`.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started Docker data image download."

[ -z "${DOCKER_USER}" ] && fail "Missing required value for DOCKER_USER." && exit 1
[ -z "${DOCKER_PASS}" ] && fail "Missing required value for DOCKER_PASS." && exit 1
[ -z "${DREVOPS_DB_DOCKER_IMAGE}" ] && fail "Destination image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1

docker image inspect "${DREVOPS_DB_DOCKER_IMAGE}" >/dev/null 2>&1 &&
  note "Found ${DREVOPS_DB_DOCKER_IMAGE} image on host." ||
  note "Not found ${DREVOPS_DB_DOCKER_IMAGE} image on host."

image_expanded_successfully=0
if [ -f "${DREVOPS_DB_DIR}/db.tar" ]; then
  note "Found archived database Docker image file ${DREVOPS_DB_DIR}/db.tar. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${DREVOPS_DB_DIR}/db.tar"

  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  if docker image inspect "${DREVOPS_DB_DOCKER_IMAGE}" >/dev/null 2>&1; then
    note "Found expanded ${DREVOPS_DB_DOCKER_IMAGE} image on host."
    image_expanded_successfully=1
  else
    note "Not found expanded ${DREVOPS_DB_DOCKER_IMAGE} image on host."
  fi
fi

if [ ! -f "${DREVOPS_DB_DIR}/db.tar" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE_BASE:-}" ]; then
  # If the image archive does not exist and base image was provided - use the
  # base image which allows "clean slate" for the database.
  note "Database Docker image was not found. Using base image ${DREVOPS_DB_DOCKER_IMAGE_BASE}."
  export DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE_BASE}"
fi

if [ "${image_expanded_successfully}" -eq 0 ]; then
  note "Downloading ${DREVOPS_DB_DOCKER_IMAGE} image from the registry."

  export DOCKER_USER="${DOCKER_USER}"
  export DOCKER_PASS="${DOCKER_PASS}"
  export DOCKER_REGISTRY="${DOCKER_REGISTRY}"
  ./scripts/drevops/login-docker.sh

  docker pull "${DOCKER_REGISTRY}/${DREVOPS_DB_DOCKER_IMAGE}"
fi

pass "Finished Docker data image download."
