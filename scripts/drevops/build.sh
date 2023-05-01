#!/usr/bin/env bash
##
# Build project.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# It is used to orchestrate other commands to "build" the project. Similar
# approach is used by hosting providers when code is received. For example,
# Acquia runs "hooks" (provided in "hooks" directory), Lagoon runs build steps
# (specified in .lagoon.yml file) etc.
#
# shellcheck disable=SC2046

# Read variables from .env and .env.local files, respecting existing environment
# variable values.
# shellcheck disable=SC1090,SC1091,SC2015,SC2155,SC2068
t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Print debug information in DrevOps scripts.
DREVOPS_DEBUG="${DREVOPS_DEBUG:-}"

# Print debug information from Docker build.
DREVOPS_DOCKER_VERBOSE="${DREVOPS_DOCKER_VERBOSE:-}"

# Print debug information from Composer install.
DREVOPS_COMPOSER_VERBOSE="${DREVOPS_COMPOSER_VERBOSE:-}"

# Print debug information from NPM install.
DREVOPS_NPM_VERBOSE="${DREVOPS_NPM_VERBOSE:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started building project ${DREVOPS_PROJECT}."
echo

[ "${DREVOPS_DOCKER_VERBOSE}" = "1" ] && docker_verbose_output="/dev/stdout" || docker_verbose_output="/dev/null"
[ "${DREVOPS_COMPOSER_VERBOSE}" = "1" ] && composer_verbose_output="/dev/stdout" || composer_verbose_output="/dev/null"
[ "${DREVOPS_NPM_VERBOSE}" = "1" ] && npm_verbose_output="/dev/stdout" || npm_verbose_output="/dev/null"

# Create an array of Docker Compose CLI options for 'exec' command as a shorthand.
# DREVOPS_*, COMPOSE_* and TERM variables will be passed to containers.
dcopts=(-T) && while IFS='' read -r line; do dcopts+=("$line"); done < <(env | cut -f1 -d= | grep "DREVOPS_\|COMPOSE_\|TERM" | sed 's/^/-e /')

# Check all pre-requisites before starting the stack.
DREVOPS_DOCTOR_CHECK_PREFLIGHT=1 ./scripts/drevops/doctor.sh

info "Validating Docker Compose configuration."
docker compose config -q && pass "Docker Compose configuration is valid." || { fail "Docker Compose configuration is invalid." && exit 1; }
echo

# Validate Composer configuration if Composer is installed.
# This is done before the containers are started to fail fast if the Composer configuration is invalid.
if command -v composer >/dev/null; then
  if [ "${DREVOPS_COMPOSER_VALIDATE_LOCK}" = "1" ]; then
    info "Validating Composer configuration, including lock file."
    composer validate --ansi --strict --no-check-all 1>"${composer_verbose_output}"
    pass "Composer configuration is valid. Lock file is up-to-date."
  else
    info "Validating composer configuration."
    composer validate --ansi --strict --no-check-all --no-check-lock 1>"${composer_verbose_output}"
    pass "Composer configuration is valid."
  fi
  echo
fi

# Create stub of local network.
# shellcheck disable=SC2015
docker network prune -f >/dev/null 2>&1 && docker network inspect amazeeio-network >/dev/null 2>&1 || docker network create amazeeio-network >/dev/null 2>&1 || true

info "Removing project containers and packages available since the previous run."
if [ -f "docker-compose.yml" ]; then docker compose down --remove-orphans --volumes >/dev/null 2>&1; fi
./scripts/drevops/clean.sh
echo

info "Building Docker images, recreating and starting containers."
note "This will take some time (use DREVOPS_DOCKER_VERBOSE=1 to see the progress)."
note "Use 'ahoy install-site' to re-install site without rebuilding containers."

if [ -n "${DREVOPS_DB_DOCKER_IMAGE:-}" ]; then
  note "Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}."
  # Always login to the registry to have access to the private images.
  ./scripts/drevops/docker-login.sh
  # Try restoring the image from the archive if it exists.
  ./scripts/drevops/docker-restore-image.sh "${DREVOPS_DB_DOCKER_IMAGE}" "${DREVOPS_DB_DIR}/db.tar"
  # If the image does not exist and base image was provided - use the base
  # image which allows "clean slate" for the database.
  if [ ! -f "${DREVOPS_DB_DIR}/db.tar" ] && [ -n "${DREVOPS_DB_DOCKER_IMAGE_BASE:-}" ]; then
    note "Database Docker image was not found. Using base image ${DREVOPS_DB_DOCKER_IMAGE_BASE}."
    export DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE_BASE}"
  fi
  echo
fi

info "Building Docker images and starting containers."

docker compose up -d --build --force-recreate 1>"${docker_verbose_output}" 2>"${docker_verbose_output}"
if docker compose logs | grep -q "\[Error\]"; then fail "Unable to build Docker images and start containers" && docker compose logs && exit 1; fi
pass "Built Docker images and started containers."
echo

# Export code built within containers before adding development dependencies.
# Usually this is needed to create a code artifact without development dependencies.
if [ -n "${DREVOPS_EXPORT_CODE_DIR:-}" ]; then
  info "Exporting built code."
  mkdir -p "${DREVOPS_EXPORT_CODE_DIR}"
  docker compose cp -L cli:"${DREVOPS_APP}"/. "${DREVOPS_EXPORT_CODE_DIR}" 2>"${composer_verbose_output}"
  pass "Exported built code."
  echo
fi

# Create data directory in the container and copy database dump file into
# container, but only if it exists, while also replacing relative directory path
# with absolute path. Note, that the DREVOPS_DB_DIR path is the same inside and
# outside the container.
if [ -f "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" ]; then
  info "Copying database file into container."
  docker compose exec ${dcopts[@]} cli bash -c "mkdir -p \${DREVOPS_DB_DIR}"
  docker compose cp -L "${DREVOPS_DB_DIR}"/"${DREVOPS_DB_FILE}" cli:"${DREVOPS_DB_DIR/.\//${DREVOPS_APP}/}"/"${DREVOPS_DB_FILE}" 2>"${composer_verbose_output}"
  pass "Copied database file into container."
  echo
fi

info "Installing development dependencies."
#
# Although we are building dependencies when Docker images are built,
# development dependencies are not installed (as they should not be installed
# for production images), so we are installing them here.
#
note "Copying development configuration files into container."
docker compose cp -L behat.yml cli:/app/ 2>"${composer_verbose_output}"
docker compose cp -L phpcs.xml cli:/app/ 2>"${composer_verbose_output}"
docker compose cp -L tests cli:/app/ 2>"${composer_verbose_output}"
docker compose cp -L .circleci cli:/app/ 2>"${composer_verbose_output}"

note "Installing all composer dependencies, including development ones."
docker compose exec ${dcopts[@]} cli bash -c " \
  if [ -n \"${GITHUB_TOKEN:-}\" ]; then export COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"${GITHUB_TOKEN:-}\"}}'; fi && \
  COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --ansi --prefer-dist --no-progress \
" 1>"${composer_verbose_output}" 2>"${composer_verbose_output}"

pass "Installed development dependencies."
echo

# Install all npm dependencies and compile FE assets.
# Note that this will create package-lock.json file if it does not exist.
# We are not re-running compilation in CI as it is not used - these assets
# are already compiled as a part of the Docker build.
if [ -n "${DREVOPS_DRUPAL_THEME:-}" ] && [ -z "${CI:-}" ]; then
  info "Installing front-end dependencies."
  docker compose exec ${dcopts[@]} cli bash -c "npm --prefix \${DREVOPS_WEBROOT}/themes/custom/\${DREVOPS_DRUPAL_THEME} install" >"${npm_verbose_output}"
  pass "Installed front-end dependencies."

  docker compose exec ${dcopts[@]} cli bash -c "cd \${DREVOPS_WEBROOT}/themes/custom/\${DREVOPS_DRUPAL_THEME} && npm run build" >"${npm_verbose_output}"
  pass "Compiled front-end dependencies."

  mkdir -p "${DREVOPS_WEBROOT}/sites/default/files"
  docker compose port cli 35729 | cut -d : -f 2 | xargs -I{} docker compose exec ${dcopts[@]} cli bash -c "echo {} > \${DREVOPS_APP}/\${DREVOPS_WEBROOT}/sites/default/files/livereload.sock"
  pass "Created Livereload socket."
  echo
fi

# Install site.
# Pass environment variables to the container from the environment.
docker compose exec ${dcopts[@]} cli bash -c "./scripts/drevops/drupal-install-site.sh"
echo

# Check that the site is available.
./scripts/drevops/doctor.sh

echo
info "Finished building project ${DREVOPS_PROJECT} ($((SECONDS / 60))m $((SECONDS % 60))s)."
