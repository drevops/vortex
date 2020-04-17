#!/usr/bin/env bash
##
# Export database image.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image archive file name. Should be provided as a fist argument or
# file name will be created automatically with a timestamp.
DOCKER_IMAGE_ARCHIVE="${DOCKER_IMAGE_ARCHIVE:-}"

# Docker image to store passed as a first argument to the script in a form of
# <org>/<repository>.
DOCKER_IMAGE="${DOCKER_IMAGE:-}"

# Docker registry name. Provide port, if required as <server_name>:<port>.
# Defaults to DockerHub.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

# The service name to capture. Optional. Defaults to "mariadb".
DOCKER_SERVICE_NAME="${DOCKER_SERVICE_NAME:-mariadb}"

# Directory with database image archive file. Optional. Defaults to "./.data".
DB_DIR="${DB_DIR:-./.data}"

# ------------------------------------------------------------------------------

echo "==> Started Docker data image export."

[ -z "${DOCKER_IMAGE}" ] && echo "ERROR: Destination image name is not specified. Please provide docker image as a variable DOCKER_IMAGE in a format <org>/<repository>." && exit 1

cid="$(docker-compose ps -q "${DOCKER_SERVICE_NAME}")"
echo "==> Found \"${DOCKER_SERVICE_NAME}\" service container with id \"${cid}\"."

new_image="${DOCKER_REGISTRY}/${DOCKER_IMAGE}"

echo "==> Committing image with name \"${new_image}\"."
iid=$(docker commit "${cid}" "${new_image}")
iid="${iid#sha256:}"
echo "==> Committed image with id \"${iid}\"."

# Create directory to store database dump.
mkdir -p "${DB_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument. Also, make sure that the extension is correct.
DOCKER_IMAGE_ARCHIVE=$([ "${1}" ] && echo "${DB_DIR}/${1//.sql/.tar}" || echo "${DB_DIR}/export_db_$(date +%Y_%m_%d_%H_%M_%S).tar")

echo "==> Exporting database image archive to \"${DOCKER_IMAGE_ARCHIVE}\" file."

[ -f "${DOCKER_IMAGE_ARCHIVE}" ] && rm -f "${DOCKER_IMAGE_ARCHIVE}"
mkdir -p "$(dirname "${DOCKER_IMAGE_ARCHIVE}")"
docker save -o "${DOCKER_IMAGE_ARCHIVE}" "${new_image}"

# Check that file was saved and output saved dump file name.
if [ -f "${DOCKER_IMAGE_ARCHIVE}" ] && [ -s "${DOCKER_IMAGE_ARCHIVE}" ]; then
  echo "==> Exported database image archive file saved \"${DOCKER_IMAGE_ARCHIVE}\"."
else
  echo "ERROR: Unable to save database image archive file \"${DOCKER_IMAGE_ARCHIVE}\"." && exit 1
fi

echo "==> Finished Docker data image export."
