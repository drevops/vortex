#!/usr/bin/env bash
##
# Export database as a Docker image.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Docker image archive file name.
DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE="${DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE:-${1}}"

# Docker image to store in a form of `<org>/<repository>`.
DREVOPS_DB_EXPORT_DOCKER_IMAGE="${DREVOPS_DB_EXPORT_DOCKER_IMAGE:-}"

# Docker registry name.
DREVOPS_DB_EXPORT_DOCKER_REGISTRY="${DREVOPS_DB_EXPORT_DOCKER_REGISTRY:-${DREVOPS_DOCKER_REGISTRY:-docker.io}}"

# The service name to capture.
DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME="${DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME:-mariadb}"

# Directory with database image archive file.
DREVOPS_DB_EXPORT_DOCKER_DIR="${DREVOPS_DB_EXPORT_DOCKER_DIR:-${DREVOPS_DB_DIR}}"

# ------------------------------------------------------------------------------

echo "==> Started Docker database image export."

[ -z "${DREVOPS_DB_EXPORT_DOCKER_IMAGE}" ] && echo "ERROR: Destination image name is not specified. Please provide docker image as a variable DREVOPS_DB_EXPORT_DOCKER_IMAGE in a format <org>/<repository>." && exit 1

cid="$(docker-compose ps -q "${DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME}")"
echo "  > Found \"${DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME}\" service container with id \"${cid}\"."

new_image="${DREVOPS_DB_EXPORT_DOCKER_REGISTRY}/${DREVOPS_DB_EXPORT_DOCKER_IMAGE}"

echo "  > Committing exported Docker image with name \"${new_image}\"."
iid=$(docker commit "${cid}" "${new_image}")
iid="${iid#sha256:}"
echo "  > Committed exported Docker image with id \"${iid}\"."

# Create directory to store database dump.
mkdir -p "${DREVOPS_DB_EXPORT_DOCKER_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument. Also, make sure that the extension is correct.
archive_file=$([ "${DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE}" ] && echo "${DREVOPS_DB_EXPORT_DOCKER_DIR}/${DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE//.sql/.tar}" || echo "${DREVOPS_DB_EXPORT_DOCKER_DIR}/export_db_$(date +%Y_%m_%d_%H_%M_%S).tar")

echo "  > Exporting database image archive to \"${archive_file}\" file."

[ -f "${archive_file}" ] && rm -f "${archive_file}"
mkdir -p "$(dirname "${archive_file}")"
docker save -o "${archive_file}" "${new_image}"

# Check that file was saved and output saved dump file name.
if [ -f "${archive_file}" ] && [ -s "${archive_file}" ]; then
  echo "  > Exported database image archive file saved \"${archive_file}\"."
else
  echo "ERROR: Unable to save database image archive file \"${archive_file}\"." && exit 1
fi

echo "==> Finished Docker database image export."
