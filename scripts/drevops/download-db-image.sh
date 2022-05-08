#!/usr/bin/env bash
##
# Download DB dump from FTP.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The Docker image containing database passed in a form of `<org>/<repository>`.
DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE="${DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE:-${DREVOPS_DB_DOCKER_IMAGE}}"

# The username of the docker registry to download the database from.
DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME:-${DREVOPS_DOCKER_REGISTRY_USERNAME}}"

# The token of the docker registry to download the database from.
DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN:-${DREVOPS_DOCKER_REGISTRY_TOKEN}}"

# The name of the Docker registry to download the database from.
DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY:-${DREVOPS_DOCKER_REGISTRY:-docker.io}}"

#-------------------------------------------------------------------------------
echo "==> Start Docker data image download."

[ -z "${DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE}" ] && echo "ERROR: Destination image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1

export DREVOPS_DOCKER_REGISTRY_USERNAME="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_USERNAME}"
export DREVOPS_DOCKER_REGISTRY_TOKEN="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY_TOKEN}"
export DREVOPS_DOCKER_REGISTRY="${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY}"
./scripts/drevops/docker-login.sh

docker pull "${DREVOPS_DB_DOWNLOAD_DOCKER_REGISTRY}/${DREVOPS_DB_DOWNLOAD_DOCKER_IMAGE}"

echo "==> Finish Docker data image download."
