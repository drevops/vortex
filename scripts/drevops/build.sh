#!/usr/bin/env bash
##
# Build project.
#
# IMPORTANT! This script runs outside the container on the host system.
# It is used to orchestrate other commands to "build" the project. Similar
# approach is used by hosting providers when code is received. For example,
# Acquia runs "hooks" (provided in "hooks" directory), Lagoon runs build steps
# (specified in .lagoon.yml file) etc.
#
# shellcheck disable=SC2046

# Read variables from .env and .env.local files, respecting existing environment
# variable values.
# shellcheck disable=SC1090,SC1091
t=$(mktemp) && export -p > "$t" && set -a && . ./.env && if [ -f ./.env.local ];then . ./.env.local;fi && set +a && . "$t" && rm "$t" && unset t

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Print debug information in DrevOps scripts.
DREVOPS_DEBUG="${DREVOPS_DEBUG:-}"

# Print debug information from Docker build.
DREVOPS_DOCKER_VERBOSE="${DREVOPS_DOCKER_VERBOSE:-}"

echo "==> Building project."

# Suppress any confirmation dialogs in descendant calls.
export DREVOPS_AHOY_CONFIRM_RESPONSE=y

## Check all pre-requisites before starting the stack.
export DREVOPS_DOCTOR_CHECK_PREFLIGHT=1 && ./scripts/drevops/doctor.sh

# Create stub of local network.
# shellcheck disable=SC2015
docker network prune -f > /dev/null 2>&1 && docker network inspect amazeeio-network > /dev/null 2>&1 || docker network create amazeeio-network > /dev/null 2>&1 || true

# Validate Composer configuration if Composer is installed.
if command -v composer > /dev/null; then
  if [ "$DREVOPS_COMPOSER_VALIDATE_LOCK" = "1" ]; then
    echo "  > Validating composer configuration, including lock file."
    composer validate --ansi --strict --no-check-all
  else
    echo "  > Validating composer configuration."
    composer validate --ansi --strict --no-check-all --no-check-lock
  fi
fi

echo "==> Removing project containers and packages available since the previous run."
ahoy clean

echo "==> Building images, recreating and starting containers."

if [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ]; then
  echo "==> Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}."
  # Always login to the registry to have access to the private images.
  ./scripts/drevops/docker-login.sh
  # Try restoring the image from the archive if it exists.
  ./scripts/drevops/docker-restore-image.sh "${DREVOPS_DB_DOCKER_IMAGE}" "${DREVOPS_DB_DIR}/db.tar"
  # If the image does not exist and base image was provided - use the base
  # image which allows '"clean slate" for the database.
  if [ ! -f "${DREVOPS_DB_DIR}/db.tar" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE_BASE}" ]; then
    echo "==> Database image was not found. Using base image ${DREVOPS_DB_DOCKER_IMAGE_BASE}."
    export DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE_BASE}"
  fi
fi

[ "${DREVOPS_DOCKER_VERBOSE}" = "1" ] && build_verbose_output="/dev/stdout" || build_verbose_output="/dev/null"
ahoy up -- --build --force-recreate > "${build_verbose_output}"

# Export code built within containers before adding development dependencies.
# Usually this is needed to create a code artifact without development
# dependencies.
if [ -n "${DREVOPS_EXPORT_CODE_DIR}" ] ; then
  echo "==> Exporting code before adding development dependencies."
  mkdir -p "${DREVOPS_EXPORT_CODE_DIR}"
  docker-compose exec --env DREVOPS_EXPORT_CODE_DIR="${DREVOPS_EXPORT_CODE_DIR}" -T cli ./scripts/drevops/export-code.sh
  # Copy from container to the host.
  docker cp -L $(docker-compose ps -q cli):"${DREVOPS_EXPORT_CODE_DIR}"/. "${DREVOPS_EXPORT_CODE_DIR}"
fi

# Create data directory in the container and copy database dump file into
# container, but only if it exists, while also replacing relative directory path
# with absolute path. Note, that the DREVOPS_DB_DIR path is the same inside and
# outside the container.
if [ -f "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" ]; then
  ahoy cli mkdir -p "${DREVOPS_DB_DIR}"
  docker cp -L "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" $(docker-compose ps -q cli):"${DREVOPS_DB_DIR/.\//${DREVOPS_APP}/}"/"${DREVOPS_DB_FILE}" \
  && echo "==> Copied database file into container."
fi

echo "==> Installing development dependencies."
#
# Although we are building dependencies when Docker images are built,
# development dependencies are not installed (as they should not be installed
# for production images), so we are installing theme here.
#
# Copy development configuration files into container.
docker cp -L behat.yml $(docker-compose ps -q cli):/app/
docker cp -L phpcs.xml $(docker-compose ps -q cli):/app/
docker cp -L tests $(docker-compose ps -q cli):/app/
# Install all composer dependencies, including development ones.
# Note that this will create composer.lock file if it does not exist.
ahoy cli "COMPOSER_MEMORY_LIMIT=-1 composer install -n --ansi --prefer-dist"

if [ -n "${DREVOPS_DRUPAL_THEME}" ]; then
  # Install all npm dependencies and compile FE assets.
  # Note that this will create package-lock.json file if it does not exist.
  [ -z "${CI}" ] && ahoy fei
  [ -z "${CI}" ] && ahoy fe
fi

# Install site (from existing DB or fresh install).
ahoy install-site

# Special handling of downloaded DB dump file in CI.
# We need to force importing of the database dump from the file into the
# database image with existing site, but only for the first time this file
# is downloaded (we do not want to import it in another stages where cached
# database image should be used instead of dump file). So we are removing the
# database dump file after import so that it is not imported again on the next
# run. But this only should be applied in CI and only if we are using database
# in image storage.
# This also prevent us from caching both dump file and an exported image,
# which would double the size of the cache.
if [ -n "${CI}" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ] && [ -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ]; then
  echo "==> Removing DB dump file in CI.";
  rm "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" || true;
fi

# Check that the site is available.
ahoy doctor

echo
echo "==> Build complete. 🚀🚀🚀 "
echo

# Show project information and a one-time login link.
DREVOPS_DRUPAL_SHOW_LOGIN_LINK=1 ahoy info
