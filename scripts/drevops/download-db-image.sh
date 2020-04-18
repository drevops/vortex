#!/usr/bin/env bash
##
# Download DB dump from FTP.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image to store passed as a first argument to the script in a form of
# <org>/<repository>.
DOCKER_IMAGE="${1:-}"

# The username of the docker registry to download the database from.
DOCKER_REGISTRY_USERNAME="${DOCKER_REGISTRY_USERNAME:-}"
# The token of the docker registry to download the database from.
DOCKER_REGISTRY_TOKEN="${DOCKER_REGISTRY_TOKEN:-}"
# Docker registry name. Provide port, if required as <server_name>:<port>.
# Defaults to DockerHub.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

#-------------------------------------------------------------------------------
echo "==> Start Docker data image download."

[ -z "${DOCKER_IMAGE}" ] && echo "ERROR: Destination image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1

./scripts/drevops/docker-login.sh

new_image="${DOCKER_REGISTRY}/${DOCKER_IMAGE}"

docker pull "${new_image}"

echo "==> Finish Docker data image download."
