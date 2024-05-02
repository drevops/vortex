#!/usr/bin/env bash
##
# Download DB dump from container image.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091,SC2015

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The container image containing database passed in a form of `<org>/<repository>`.
DREVOPS_DB_IMAGE="${DREVOPS_DB_IMAGE:-}"

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY:-${DREVOPS_CONTAINER_REGISTRY:-docker.io}}"

# The username to login into the container registry.
DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_USER:-${DREVOPS_CONTAINER_REGISTRY_USER:-}}"

# The password to login into the container registry.
DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS:-${DREVOPS_CONTAINER_REGISTRY_PASS:-}}"

#-------------------------------------------------------------------------------

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

info "Started database data container image download."

[ -z "${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY}" ] && fail "Missing required value for DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY." && exit 1
[ -z "${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_USER}" ] && fail "Missing required value for DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_USER." && exit 1
[ -z "${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS}" ] && fail "Missing required value for DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS." && exit 1
[ -z "${DREVOPS_DB_IMAGE}" ] && fail "Destination image name is not specified. Please provide container image name as a first argument to this script in a format <org>/<repository>." && exit 1

docker image inspect "${DREVOPS_DB_IMAGE}" >/dev/null 2>&1 &&
  note "Found ${DREVOPS_DB_IMAGE} image on host." ||
  note "Not found ${DREVOPS_DB_IMAGE} image on host."

image_expanded_successfully=0
if [ -f "${DREVOPS_DB_DIR}/db.tar" ]; then
  note "Found archived database container image file ${DREVOPS_DB_DIR}/db.tar. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${DREVOPS_DB_DIR}/db.tar"

  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  if docker image inspect "${DREVOPS_DB_IMAGE}" >/dev/null 2>&1; then
    note "Found expanded ${DREVOPS_DB_IMAGE} image on host."
    image_expanded_successfully=1
  else
    note "Not found expanded ${DREVOPS_DB_IMAGE} image on host."
  fi
fi

if [ "${image_expanded_successfully}" -eq 0 ]; then
  if [ ! -f "${DREVOPS_DB_DIR}/db.tar" ] && [ -n "${DREVOPS_DB_IMAGE_BASE:-}" ]; then
    # If the image archive does not exist and base image was provided - use the
    # base image which allows "clean slate" for the database.
    note "Database container image was not found. Using base image ${DREVOPS_DB_IMAGE_BASE}."
    export DREVOPS_DB_IMAGE="${DREVOPS_DB_IMAGE_BASE}"
  fi

  note "Downloading ${DREVOPS_DB_IMAGE} image from the registry."

  export DREVOPS_CONTAINER_REGISTRY="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY}"
  export DREVOPS_CONTAINER_REGISTRY_USER="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_USER}"
  export DREVOPS_CONTAINER_REGISTRY_PASS="${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS}"
  ./scripts/drevops/login-container-registry.sh

  docker pull "${DREVOPS_DOWNLOAD_DB_CONTAINER_REGISTRY}/${DREVOPS_DB_IMAGE}"
fi

pass "Finished database data container image download."
