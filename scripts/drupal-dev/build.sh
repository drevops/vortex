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

echo "==> Building project"

CUR_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

pushd "${CUR_DIR}" > /dev/null || exit 1

# Check all pre-requisites before starting the stack.
DOCTOR_CHECK_PREFLIGHT=1 ahoy doctor

# Always "clean" project containers and files since the previous run.
ahoy clean

# Build images, recreate and start containers.
ahoy up -- --build --force-recreate

# Export code built within containers before adding development dependencies.
# This is not used locally, but used in CI.
ahoy export-code

# Install development dependencies.
#
# Although we are building dependencies when Docker images are built,
# development dependencies are not installed (as they should not be installed
# for production images), so we are installing theme here.
#
# Create data directory in the container and copy database dump from data
# directory on host into container.
ahoy cli mkdir -p /tmp/data && docker cp -L .data/db.sql $(docker-compose ps -q cli):/tmp/data/db.sql
# Install all composer dependencies, including development ones.
# Note that this will create/update composer.lock file.
ahoy cli "composer install -n --ansi --prefer-dist --no-suggest"
# Install all npm dependencies and compile FE assets.
# Note that this will create/update package-lock.json file.
ahoy cli "npm install" && ahoy fe
# Copy development configuration files into container.
docker cp -L behat.yml $(docker-compose ps -q cli):/app/
docker cp -L phpcs.xml $(docker-compose ps -q cli):/app/
docker cp -L phpunit.xml $(docker-compose ps -q cli):/app/
docker cp -L tests $(docker-compose ps -q cli):/app/

# Install site (from existing DB or fresh install).
ahoy install-site

# Check that the site is available.
ahoy doctor

echo "==> Build complete"

# Show project information and a one-time login link.
SHOW_LOGIN_LINK=1 ahoy info

popd > /dev/null || exit 1
