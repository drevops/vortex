#!/usr/bin/env bash
##
# Download DB dump from FTP.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image to store passed as a first argument to the script in a form of
# <org>/<repository>.
DOCKER_IMAGE="${1:-}"

# Image tag to apply to the newly created image as a first argument to the
# script. Defaults to "latest".
# Note that creating a custom tag and "latest" requires running this script twice.
DOCKER_IMAGE_TAG="${2:-latest}"

# The username of the docker registry to download the database from.
DOCKER_REGISTRY_USERNAME="${DOCKER_USERNAME:-}"
# The token of the docker registry to download the database from.
DOCKER_REGISTRY_TOKEN="${DOCKER_TOKEN:-}"
# Docker registry name. Provide port, if required as <server_name>:<port>.
# Defaults to DockerHub.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

#-------------------------------------------------------------------------------
echo "==> Start Docker data image download."

[ -z "${DOCKER_IMAGE}" ] && echo "ERROR: Destination image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1

if docker system info | grep -q "${DOCKER_REGISTRY}"; then
  echo "==> Already logged in to registry \"${DOCKER_REGISTRY}\"."
else
  echo "==> Logging in to registry \"${DOCKER_REGISTRY}\"."
  [ -z "${DOCKER_REGISTRY_USERNAME}" ] && echo "ERROR: DOCKER_REGISTRY_USERNAME is empty." && exit 1
  [ -z "${DOCKER_REGISTRY_TOKEN}" ] && echo "ERROR: DOCKER_REGISTRY_TOKEN is empty." && exit 1
  docker login --username "${DOCKER_REGISTRY_USERNAME}" --password "${DOCKER_REGISTRY_TOKEN}" "${DOCKER_REGISTRY}"
fi

new_image="${DOCKER_REGISTRY}/${DOCKER_IMAGE}:${DOCKER_IMAGE_TAG}"

docker pull "${new_image}"

echo "==> Finish Docker data image download."
