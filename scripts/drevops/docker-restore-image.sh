#!/usr/bin/env bash
##
# Restore Docker image from archive.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image archive file name.
DREVOPS_DOCKER_RESTORE_IMAGE="${1:-}"

# Docker image archive file to restore passed as a second argument to this script.
DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE="${2:-}"

#-------------------------------------------------------------------------------

echo "==> Started Docker image restore"

[ -z "${DREVOPS_DOCKER_RESTORE_IMAGE}" ] && echo "ERROR: image name is not specified. Provide Docker image name as a first argument to this script in a format <org>/<repository>." && exit 1
[ -z "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}" ] && echo "ERROR: image archive file name is not specified. Provide Docker image archive file name as a second argument to this script." && exit 1

docker image inspect "${DREVOPS_DOCKER_RESTORE_IMAGE}" >/dev/null 2>&1 \
  && echo "  > Found ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host." \
  || echo "  > Not found ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host."

if [ -f "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}" ]; then
  echo "  > Found archived database Docker image file ${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}. Expanding..."
  # Always use archived image, even if such image already exists on the host.
  docker load -q --input "${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}"
  # Check that image was expanded and now exists on the host or notify
  # that it will be downloaded from the registry.
  docker image inspect "${DREVOPS_DOCKER_RESTORE_IMAGE}" >/dev/null 2>&1 \
    && echo "  > Found expanded ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host." \
    || echo "  > Not found expanded ${DREVOPS_DOCKER_RESTORE_IMAGE} image on host. The image will be pulled from the registry."
else
  echo "  > Not found archived database Docker image file ${DREVOPS_DOCKER_RESTORE_ARCHIVE_FILE}."
fi

echo "==> Finished Docker image restore"
