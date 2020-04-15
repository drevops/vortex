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

echo "==> Building project"

# Read variables from .env file, respecting existing environment variable values.
# shellcheck disable=SC1090,SC1091
t=$(mktemp) && export -p > "$t" && set -a && . ./.env && set +a && . "$t" && rm "$t" && unset t

## Check all pre-requisites before starting the stack.
DOCTOR_CHECK_PREFLIGHT=1 ahoy doctor

# Create stub of local network.
# shellcheck disable=SC2015
docker network prune -f > /dev/null && docker network inspect amazeeio-network > /dev/null || docker network create amazeeio-network

# Validate Composer configuration if Composer is installed.
if command -v composer > /dev/null; then
  if [ "$COMPOSER_VALIDATE_LOCK" = "1" ]; then
    echo "==> Validating composer configuration, including lock file"
    composer validate --ansi --strict --no-check-all
  else
    echo "==> Validating composer configuration"
    composer validate --ansi --strict --no-check-all --no-check-lock
  fi
fi

echo "==> Removing project containers and packages available since the previous run"
ahoy clean

echo "==> Building images, recreating and starting containers"

if [ -n "${DATABASE_IMAGE}" ]; then
  echo "==> Using Docker data image ${DATABASE_IMAGE}."
  # Try restoring the image from the archive if it exists.
  ./scripts/drevops/docker-restore-image.sh "${DATABASE_IMAGE}" "${DB_DIR}/db.tar"
fi

# Running 'ahoy up' directly will show the build progress.
[ -n "${BUILD_VERBOSE}" ] && BUILD_VERBOSE_OUTPUT="/dev/stdout" || BUILD_VERBOSE_OUTPUT="/dev/null"
ahoy up -- --build --force-recreate > "${BUILD_VERBOSE_OUTPUT}"

# Export code built within containers before adding development dependencies.
# This usually is not used locally, but used when production-grade code (without
# dev dependencies) is used.
if [ -n "${BUILD_EXPORT_DIR}" ] ; then
  echo "==> Exporting code before adding development dependencies"
  mkdir -p "${BUILD_EXPORT_DIR}"
  docker-compose exec --env BUILD_EXPORT_DIR="${BUILD_EXPORT_DIR}" -T cli ./scripts/drevops/export-code.sh
  docker cp -L $(docker-compose ps -q cli):"${BUILD_EXPORT_DIR}"/. "${BUILD_EXPORT_DIR}"
fi

# Create data directory in the container and copy database dump file into
# container, but only if it exists, while also replacing relative directory path
# with absolute path. Note, that the DB_DIR path is the same inside and outside
# of the container.
[ -f "${DB_DIR}"/"${DB_FILE}" ] && ahoy cli mkdir -p "${DB_DIR}" && docker cp -L "${DB_DIR}"/"${DB_FILE}" $(docker-compose ps -q cli):"${DB_DIR/.\//${APP}/}"/"${DB_FILE}"

echo "==> Installing development dependencies"
#
# Although we are building dependencies when Docker images are built,
# development dependencies are not installed (as they should not be installed
# for production images), so we are installing theme here.
#
# Copy development configuration files into container.
docker cp -L behat.yml $(docker-compose ps -q cli):/app/
docker cp -L phpcs.xml $(docker-compose ps -q cli):/app/
docker cp -L phpunit.xml $(docker-compose ps -q cli):/app/
docker cp -L tests $(docker-compose ps -q cli):/app/
# Install all composer dependencies, including development ones.
# Note that this will create/update composer.lock file.
ahoy cli "composer install -n --ansi --prefer-dist --no-suggest"
# Install all npm dependencies and compile FE assets.
# Note that this will create/update package-lock.json file.
ahoy cli "npm --prefix docroot/sites/all/themes/custom/your_site_theme install --no-audit --quiet --no-progress" && ahoy fe

# Install site (from existing DB or fresh install).
ahoy install-site

# Check that the site is available.
ahoy doctor

echo "==> Build complete"

# Show project information and a one-time login link.
SHOW_LOGIN_LINK=1 ahoy info
