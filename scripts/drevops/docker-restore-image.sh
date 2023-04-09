#!/usr/bin/env bash
##
# Restore Docker image from archive.
#
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image archive file name.
DREVOPS_DOCKER_RESTORE_IMAGE="${1:-}"

# Docker image archive file to restore passed as a second argument to this script.
DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE="${2:-}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[32m  [OK] %s\033[0m\n" "$1" || printf "  [OK] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started Docker image restore"

[ -z "${DREVOPS_DOCKER_RESTORE_IMAGE}" ] && fail "image name is not specified. Provide Docker image name as a first argument to this script in a format <org>/<repository>." && exit 1
[ -z "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}" ] && fail "image archive file name is not specified. Provide Docker image archive file name as a second argument to this script." && exit 1

docker image inspect "${DREVOPS_DOCKER_RESTORE_IMAGE}" >/dev/null 2>&1 \
  && note "Found ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host." \
  || note "Not found ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host."

if [ -f "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}" ]; then
  note "Found archived database Docker image file ${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}"
  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  docker image inspect "${DREVOPS_DOCKER_RESTORE_IMAGE}" >/dev/null 2>&1 \
    && note "Found expanded ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host." \
    || note "Not found expanded ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host. The image will be pulled from the registry."
else
  note "Not found archived database Docker image file ${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}."
fi

pass "Finished Docker image restore"
