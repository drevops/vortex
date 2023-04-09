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

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[32m  [OK] %s\033[0m\n" "$1" || printf "  [OK] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && [ -t 1 ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started Docker database image export."

[ -z "${DREVOPS_DB_EXPORT_DOCKER_IMAGE}" ] && fail "Destination image name is not specified. Please provide docker image as a variable DREVOPS_DB_EXPORT_DOCKER_IMAGE in a format <org>/<repository>." && exit 1

cid="$(docker-compose ps -q "${DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME}")"
note "Found \"${DREVOPS_DB_EXPORT_DOCKER_SERVICE_NAME}\" service container with id \"${cid}\"."

new_image="${DREVOPS_DB_EXPORT_DOCKER_REGISTRY}/${DREVOPS_DB_EXPORT_DOCKER_IMAGE}"

note "Committing exported Docker image with name \"${new_image}\"."
iid=$(docker commit "${cid}" "${new_image}")
iid="${iid#sha256:}"
note "Committed exported Docker image with id \"${iid}\"."

# Create directory to store database dump.
mkdir -p "${DREVOPS_DB_EXPORT_DOCKER_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument. Also, make sure that the extension is correct.
archive_file=$([ "${DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE}" ] && echo "${DREVOPS_DB_EXPORT_DOCKER_DIR}/${DREVOPS_DB_EXPORT_DOCKER_ARCHIVE_FILE//.sql/.tar}" || echo "${DREVOPS_DB_EXPORT_DOCKER_DIR}/export_db_$(date +%Y_%m_%d_%H_%M_%S).tar")

note "Exporting database image archive to \"${archive_file}\" file."

[ -f "${archive_file}" ] && rm -f "${archive_file}"
mkdir -p "$(dirname "${archive_file}")"
docker save -o "${archive_file}" "${new_image}"

# Check that file was saved and output saved dump file name.
if [ -f "${archive_file}" ] && [ -s "${archive_file}" ]; then
  note "Exported database image archive file saved \"${archive_file}\"."
else
  fail "Unable to save database image archive file \"${archive_file}\"." && exit 1
fi

pass "Finished Docker database image export."
