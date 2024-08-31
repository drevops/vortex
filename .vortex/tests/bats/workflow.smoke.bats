#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Idempotence" {
  prepare_sut "Starting idempotence tests in build directory ${BUILD_DIR}"

  step "Download DEMO database"
  assert_ahoy_download_db

  step "Build project"
  assert_ahoy_build
  assert_gitignore
  assert_ahoy_test_bdd_fast

  # Running build several times should result in the same project build results.
  step "Re-build project"
  assert_ahoy_build
  # Skip committing of the files.
  assert_gitignore 1
  assert_ahoy_test_bdd_fast
}

# Make sure to run with `TEST_GITHUB_TOKEN=working_test_token bats...` or this test will fail.
@test "GitHub token" {
  prepare_sut "Starting GitHub token tests in build directory ${BUILD_DIR}"

  step "Add private package"
  rm composer.lock || true
  composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git
  jq --indent 4 '.require += {"drevops/test-private-package": "^1"}' composer.json >composer.json.tmp && mv -f composer.json.tmp composer.json

  export VORTEX_CONTAINER_REGISTRY_USER="${TEST_VORTEX_CONTAINER_REGISTRY_USER?Test Docker user is not set}"
  export VORTEX_CONTAINER_REGISTRY_PASS="${TEST_VORTEX_CONTAINER_REGISTRY_PASS?Test Docker pass is not set}"

  step "Build without a GITHUB_TOKEN token"
  unset GITHUB_TOKEN
  process_ahoyyml
  run ahoy build
  assert_failure

  step "Build with a GITHUB_TOKEN token"
  export GITHUB_TOKEN="${TEST_GITHUB_TOKEN}"
  process_ahoyyml
  run ahoy build
  assert_success
}

@test "Docker compose, no Ahoy" {
  prepare_sut "Starting Docker compose without Ahoy tests in build directory ${BUILD_DIR}"

  step "Download DEMO database"
  assert_ahoy_download_db

  step "Build project"
  ahoy reset

  substep "Building stack"
  docker compose up -d --build --force-recreate >&3

  substep "Installing dependencies"
  docker compose exec -T cli composer install --prefer-dist >&3

  substep "Provisioning"

  # Copy DB into container for the cases when the volumes are not mounted.
  # This will not be a case locally.
  if [ "${VORTEX_DEV_VOLUMES_MOUNTED}" != "1" ]; then
    if [ -f .data/db.sql ]; then
      docker compose exec cli mkdir -p .data
      docker compose cp -L .data/db.sql cli:/app/.data/db.sql
    fi
  fi

  docker compose exec -T cli ./scripts/vortex/provision.sh >&3

  sync_to_host
  assert_gitignore
  assert_ahoy_test_bdd_fast
}
