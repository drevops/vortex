#!/usr/bin/env bash
##
# Restore Docker image from archive.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image passed as a first argument to this script in a form of
# <org>/<repository>.
DOCKER_IMAGE="${1:-}"

# Docker image archive file to restore passed as a second argument to this script.
DOCKER_IMAGE_ARCHIVE="${2:-}"

#-------------------------------------------------------------------------------

[ -z "${DOCKER_IMAGE}" ] && echo "ERROR: image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1
[ -z "${DOCKER_IMAGE_ARCHIVE}" ] && echo "ERROR: image archive file name is not specified. Please provide docker image archive file name as a second argument to this script." && exit 1

docker image inspect "${DOCKER_IMAGE}" >/dev/null 2>&1 && echo "==> Found ${DOCKER_IMAGE} image on host." || echo "==> Not found ${DOCKER_IMAGE} image on host."

if [ -f "${DOCKER_IMAGE_ARCHIVE}" ]; then
  echo "==> Found archived database Docker image file ${DOCKER_IMAGE_ARCHIVE}. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${DOCKER_IMAGE_ARCHIVE}"
  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  docker image inspect "${DOCKER_IMAGE}" >/dev/null 2>&1 && echo "==> Found expanded ${DOCKER_IMAGE} image on host." || echo "==> Not found expanded ${DOCKER_IMAGE} image on host. The image will be pulled from the registry."
else
  echo "==> Not found archived database Docker image file ${DOCKER_IMAGE_ARCHIVE}."
fi
