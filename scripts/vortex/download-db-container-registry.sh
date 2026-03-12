#!/usr/bin/env bash
##
# Download DB dump from container image.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091,SC2015

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Database index suffix. When set (e.g., "2"), all DB-related variable lookups
# use the indexed variant (e.g., VORTEX_DB2_IMAGE instead of VORTEX_DB_IMAGE).
_db_index="${VORTEX_DB_INDEX:-}"

# The container image containing database passed in a form of `<org>/<repository>`.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY_IMAGE"
_vs="VORTEX_DB${_db_index}_IMAGE"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE="${!_v:-${!_vs:-}}"

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="${!_v:-${VORTEX_CONTAINER_REGISTRY:-docker.io}}"

# The username to login into the container registry.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY_USER"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="${!_v:-${VORTEX_CONTAINER_REGISTRY_USER:-}}"

# The password to login into the container registry.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY_PASS"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="${!_v:-${VORTEX_CONTAINER_REGISTRY_PASS:-}}"

# Directory with database dump file.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY_DB_DIR"
_vs="VORTEX_DOWNLOAD_DB${_db_index}_DIR"
_vss="VORTEX_DB${_db_index}_DIR"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR="${!_v:-${!_vs:-${!_vss:-./.data}}}"

# The base container image used as a fallback when the archive does not exist.
_v="VORTEX_DOWNLOAD_DB${_db_index}_CONTAINER_REGISTRY_IMAGE_BASE"
_vs="VORTEX_DB${_db_index}_IMAGE_BASE"
VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE_BASE="${!_v:-${!_vs:-}}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database data container image download."

[ -z "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY or VORTEX_CONTAINER_REGISTRY." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER or VORTEX_CONTAINER_REGISTRY_USER." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS}" ] && fail "Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS or VORTEX_CONTAINER_REGISTRY_PASS." && exit 1
[ -z "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE}" ] && fail "Destination image name is not specified. Please provide VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE or VORTEX_DB_IMAGE in a format <org>/<repository>." && exit 1

docker image inspect "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE}" >/dev/null 2>&1 &&
  note "Found ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE} image on host." ||
  note "Not found ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE} image on host."

image_expanded_successfully=0
if [ -f "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR}/db.tar" ]; then
  task "Found archived database container image file ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR}/db.tar. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR}/db.tar"

  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  if docker image inspect "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE}" >/dev/null 2>&1; then
    note "Found expanded ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE} image on host."
    image_expanded_successfully=1
  else
    note "Not found expanded ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE} image on host."
  fi
fi

if [ "${image_expanded_successfully}" -eq 0 ]; then
  if [ ! -f "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_DB_DIR}/db.tar" ] && [ -n "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE_BASE:-}" ]; then
    # If the image archive does not exist and base image was provided - use the
    # base image which allows "clean slate" for the database.
    note "Database container image was not found. Using base image ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE_BASE}."
    export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE="${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE_BASE}"
  fi

  task "Downloading ${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE} image from the registry."

  export VORTEX_CONTAINER_REGISTRY="${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY}"
  export VORTEX_CONTAINER_REGISTRY_USER="${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER}"
  export VORTEX_CONTAINER_REGISTRY_PASS="${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS}"
  ./scripts/vortex/login-container-registry.sh

  docker pull "${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY}/${VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_IMAGE}"
fi

pass "Finished database data container image download."
