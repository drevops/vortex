#!/usr/bin/env bash
# shellcheck disable=SC2046
##
# Build project.
#
# IMPORTANT! This script runs outside of the container on the host system.
# It is used to orchestrate other commands to "build" the project. Similar
# approach is used by hosting providers when code is received. For example,
# Acquia runs "hooks" (provided in "hooks" directory), Lagoon runs build steps
# (specified in .lagoon.yml file) etc.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

echo "==> Building project."

# Suppress any confirmation dialogs in descendant calls.
export DREVOPS_CONFIRM_RESPONSE=y

# Read variables from .env file, respecting existing environment variable values.
# shellcheck disable=SC1090,SC1091
t=$(mktemp) && export -p > "$t" && set -a && . ./.env && set +a && . "$t" && rm "$t" && unset t

## Check all pre-requisites before starting the stack.
DREVOPS_DOCTOR_CHECK_PREFLIGHT=1 ahoy doctor

# Create stub of local network.
# shellcheck disable=SC2015
docker network prune -f > /dev/null && docker network inspect amazeeio-network > /dev/null || docker network create amazeeio-network

# Validate Composer configuration if Composer is installed.
if command -v composer > /dev/null; then
  if [ "$DREVOPS_COMPOSER_VALIDATE_LOCK" = "1" ]; then
    echo "==> Validating composer configuration, including lock file."
    composer validate --ansi --strict --no-check-all
  else
    echo "==> Validating composer configuration."
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
fi

# Running 'ahoy up' directly will show the build progress.
[ "${DREVOPS_BUILD_VERBOSE}" = "1" ] && build_verbose_output="/dev/stdout" || build_verbose_output="/dev/null"
ahoy up -- --build --force-recreate > "${build_verbose_output}"

# Export code built within containers before adding development dependencies.
# This usually is not used locally, but used when production-grade code (without
# dev dependencies) is used.
if [ -n "${DREVOPS_BUILD_CODE_EXPORT_DIR}" ] ; then
  echo "==> Exporting code before adding development dependencies."
  mkdir -p "${DREVOPS_BUILD_CODE_EXPORT_DIR}"
  docker-compose exec --env DREVOPS_BUILD_CODE_EXPORT_DIR="${DREVOPS_BUILD_CODE_EXPORT_DIR}" -T cli ./scripts/drevops/export-code.sh
  docker cp -L $(docker-compose ps -q cli):"${DREVOPS_BUILD_CODE_EXPORT_DIR}"/. "${DREVOPS_BUILD_CODE_EXPORT_DIR}"
fi

# Create data directory in the container and copy database dump file into
# container, but only if it exists, while also replacing relative directory path
# with absolute path. Note, that the DREVOPS_DB_DIR path is the same inside and outside
# of the container.
[ -f "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" ] && ahoy cli mkdir -p "${DREVOPS_DB_DIR}" && docker cp -L "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" $(docker-compose ps -q cli):"${DREVOPS_DB_DIR/.\//${DREVOPS_APP}/}"/"${DREVOPS_DB_FILE}" && echo "==> Copied database file into container."

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
# Note that this will create/update composer.lock file.
ahoy cli "COMPOSER_MEMORY_LIMIT=-1 composer install -n --ansi --prefer-dist"

if [ -n "${DREVOPS_DRUPAL_THEME}" ]; then
  # Install all npm dependencies and compile FE assets.
  # Note that this will create/update package-lock.json file.
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
# database dump file after import so it is not imported again on the next run.
# But this only should be applied in CI and only if we are using database in
# image storage.
# This also prevent us from caching both dump file and an exported image,
# which would double the size of the cache.
if [ -n "${CI}" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ] && [ -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ]; then echo "==> Removing DB dump file in CI."; rm "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" || true; fi

# Check that the site is available.
ahoy doctor

echo "==> Build complete."

# Show project information and a one-time login link.
DREVOPS_SHOW_LOGIN_LINK=1 ahoy info
