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

# Print debug information from Composer install.
DREVOPS_COMPOSER_VERBOSE="${DREVOPS_COMPOSER_VERBOSE:-}"

# Print debug information from NPM install.
DREVOPS_NPM_VERBOSE="${DREVOPS_NPM_VERBOSE:-}"

# Only build and export code.
DREVOPS_EXPORT_CODE_ONLY="${DREVOPS_EXPORT_CODE_ONLY:-}"

echo "INFO Building project."
echo "     Adjust build verbosity by setting variable to '1':"
echo "     - DREVOPS_DEBUG             Verbose DrevOps scripts."
echo "     - DREVOPS_DOCKER_VERBOSE    Verbose Docker build."
echo "     - DREVOPS_COMPOSER_VERBOSE  Verbose Composer install."
echo "     - DREVOPS_NPM_VERBOSE       Verbose NPM install."
echo

# Suppress any confirmation dialogs in descendant calls.
export DREVOPS_AHOY_CONFIRM_RESPONSE=y

## Check all pre-requisites before starting the stack.
export DREVOPS_DOCTOR_CHECK_PREFLIGHT=1 && ./scripts/drevops/doctor.sh

[ "${DREVOPS_DOCKER_VERBOSE}" = "1" ] && docker_verbose_output="/dev/stdout" || docker_verbose_output="/dev/null"
[ "${DREVOPS_COMPOSER_VERBOSE}" = "1" ] && composer_verbose_output="/dev/stdout" || composer_verbose_output="/dev/null"
[ "${DREVOPS_NPM_VERBOSE}" = "1" ] && npm_verbose_output="/dev/stdout" || npm_verbose_output="/dev/null"

# Validate Composer configuration if Composer is installed.
if command -v composer > /dev/null; then
  if [ "$DREVOPS_COMPOSER_VALIDATE_LOCK" = "1" ]; then
    echo "INFO Validating composer configuration, including lock file."
    composer validate --ansi --strict --no-check-all 1>"${composer_verbose_output}"
    echo "  OK Validated composer.json."
  else
    echo "INFO Validating composer configuration."
    composer validate --ansi --strict --no-check-all --no-check-lock 1>"${composer_verbose_output}"
    echo "  OK Validated composer.json."
  fi
  echo
fi

# Create stub of local network.
# shellcheck disable=SC2015
docker network prune -f > /dev/null 2>&1 && docker network inspect amazeeio-network > /dev/null 2>&1 || docker network create amazeeio-network > /dev/null 2>&1 || true

echo "INFO Removing project containers and packages available since the previous run."
ahoy clean
echo

echo "INFO Building Docker images, recreating and starting containers."
echo "     This will take some time."
echo "     Consider 'ahoy install-site' to re-install site without rebuilding containers."

if [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ]; then
  echo "     > Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}."
  # Always login to the registry to have access to the private images.
  ./scripts/drevops/docker-login.sh
  # Try restoring the image from the archive if it exists.
  ./scripts/drevops/docker-restore-image.sh "${DREVOPS_DB_DOCKER_IMAGE}" "${DREVOPS_DB_DIR}/db.tar"
  # If the image does not exist and base image was provided - use the base
  # image which allows "clean slate" for the database.
  if [ ! -f "${DREVOPS_DB_DIR}/db.tar" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE_BASE}" ]; then
    echo "     > Database Docker image was not found. Using base image ${DREVOPS_DB_DOCKER_IMAGE_BASE}."
    export DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE_BASE}"
  fi
fi

ahoy up -- --build --force-recreate 1>"${docker_verbose_output}" 2>"${docker_verbose_output}"
echo "  OK Built Docker images and started containers."
echo

# Export code built within containers before adding development dependencies.
# Usually this is needed to create a code artifact without development
# dependencies.
if [ -n "${DREVOPS_EXPORT_CODE_DIR}" ] ; then
  echo "INFO Exporting built code."
  mkdir -p "${DREVOPS_EXPORT_CODE_DIR}"
  docker-compose exec --env DREVOPS_EXPORT_CODE_DIR="${DREVOPS_EXPORT_CODE_DIR}" -T cli ./scripts/drevops/export-code.sh
  # Copy from container to the host.
  docker cp -L $(docker-compose ps -q cli):"${DREVOPS_EXPORT_CODE_DIR}"/. "${DREVOPS_EXPORT_CODE_DIR}"
  echo "  OK Exported built code."
  echo
  [ -n "${DREVOPS_EXPORT_CODE_ONLY}" ] && echo "     Skipping the rest of the build" && exit 0
fi

# Create data directory in the container and copy database dump file into
# container, but only if it exists, while also replacing relative directory path
# with absolute path. Note, that the DREVOPS_DB_DIR path is the same inside and
# outside the container.
if [ -f "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" ]; then
  echo "INFO Copying database file into container."
  ahoy cli mkdir -p "${DREVOPS_DB_DIR}"
  docker cp -L "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" $(docker-compose ps -q cli):"${DREVOPS_DB_DIR/.\//${DREVOPS_APP}/}"/"${DREVOPS_DB_FILE}"
  echo "  OK Copied database file into container."
  echo
fi

echo "INFO Installing development dependencies."
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
ahoy cli "COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --ansi --prefer-dist --no-progress" 1>"${composer_verbose_output}" 2>"${composer_verbose_output}"
echo "  OK Installed development dependencies."
echo

if [ -n "${DREVOPS_DRUPAL_THEME}" ]; then
  echo "INFO Installing front-end dependencies."
  # Install all npm dependencies and compile FE assets.
  # Note that this will create package-lock.json file if it does not exist.
  # We are not re-running compilation in CI as it is not used - these assets
  # are already compiled as a part of the Docker build.
  [ -z "${CI}" ] && ahoy fei > "${npm_verbose_output}"
  echo "  OK Installed front-end dependencies."
  [ -z "${CI}" ] && ahoy fe > "${npm_verbose_output}"
  echo "  OK Compiled front-end dependencies."
  echo
fi

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
# which would double the size of the CI cache.
if [ -n "${CI}" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ] && [ -f "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" ]; then
  echo "INFO Removing DB dump file in CI.";
  rm "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}" || true;
  echo "  OK Removed DB dump file in CI."
  echo
fi

# Check that the site is available.
ahoy doctor

echo "INFO Build complete."

# Show project information and a one-time login link.
DREVOPS_DRUPAL_SHOW_LOGIN_LINK=1 ahoy info
