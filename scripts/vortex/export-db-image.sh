#!/usr/bin/env bash
##
# Export database as a container image.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Container image archive file name.
VORTEX_DB_EXPORT_IMAGE_ARCHIVE_FILE="${VORTEX_DB_EXPORT_IMAGE_ARCHIVE_FILE:-${1}}"

# Container image to store in a form of `<org>/<repository>`.
VORTEX_DB_EXPORT_IMAGE="${VORTEX_DB_EXPORT_IMAGE:-}"

# Container registry name.
VORTEX_DB_EXPORT_CONTAINER_REGISTRY="${VORTEX_DB_EXPORT_CONTAINER_REGISTRY:-${VORTEX_CONTAINER_REGISTRY:-docker.io}}"

# The service name to capture.
VORTEX_DB_EXPORT_SERVICE_NAME="${VORTEX_DB_EXPORT_SERVICE_NAME:-mariadb}"

# Directory with database image archive file.
VORTEX_DB_EXPORT_IMAGE_DIR="${VORTEX_DB_EXPORT_IMAGE_DIR:-${VORTEX_DB_DIR}}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#shellcheck disable=SC2043
for cmd in docker; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database data container image export."

[ -z "${VORTEX_DB_EXPORT_IMAGE}" ] && fail "Destination image name is not specified. Please provide container image as a variable VORTEX_DB_EXPORT_IMAGE in a format <org>/<repository>." && exit 1

cid="$(docker compose ps -q "${VORTEX_DB_EXPORT_SERVICE_NAME}")"
note "Found ${VORTEX_DB_EXPORT_SERVICE_NAME} service container with id ${cid}."

new_image="${VORTEX_DB_EXPORT_CONTAINER_REGISTRY}/${VORTEX_DB_EXPORT_IMAGE}"

note "Locking and unlocking tables before upgrade."
docker compose exec -T "${VORTEX_DB_EXPORT_SERVICE_NAME}" mysql -e "FLUSH TABLES WITH READ LOCK;"
sleep 5
docker compose exec -T "${VORTEX_DB_EXPORT_SERVICE_NAME}" mysql -e "UNLOCK TABLES;"

note "Running forced service upgrade."
docker compose exec -T "${VORTEX_DB_EXPORT_SERVICE_NAME}" sh -c "mariadb-upgrade --force || mariadb-upgrade --force"

note "Locking tables after upgrade."
docker compose exec -T "${VORTEX_DB_EXPORT_SERVICE_NAME}" mysql -e "FLUSH TABLES WITH READ LOCK;"

note "Committing exported container image with name ${new_image}."
iid=$(docker commit "${cid}" "${new_image}")
iid="${iid#sha256:}"
note "Committed exported container image with id ${iid}."

# Create directory to store database dump.
mkdir -p "${VORTEX_DB_EXPORT_IMAGE_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument. Also, make sure that the extension is correct.
archive_file=$([ "${VORTEX_DB_EXPORT_IMAGE_ARCHIVE_FILE}" ] && echo "${VORTEX_DB_EXPORT_IMAGE_DIR}/${VORTEX_DB_EXPORT_IMAGE_ARCHIVE_FILE//.sql/.tar}" || echo "${VORTEX_DB_EXPORT_IMAGE_DIR}/export_db_$(date +%Y%m%d_%H%M%S).tar")

note "Exporting database image archive to file ${archive_file}."

[ -f "${archive_file}" ] && rm -f "${archive_file}"
mkdir -p "$(dirname "${archive_file}")"
docker save -o "${archive_file}" "${new_image}"

# Check that file was saved and output saved dump file name.
if [ -f "${archive_file}" ] && [ -s "${archive_file}" ]; then
  note "Saved exported database image archive file ${archive_file}."
else
  # LCOV_EXCL_START
  fail "Unable to save database image archive file ${archive_file}." && exit 1
  # LCOV_EXCL_STOP
fi

pass "Finished database data container image export."
