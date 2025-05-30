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
  if [ "${VORTEX_DEV_VOLUMES_SKIP_MOUNT:-0}" = "1" ]; then
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
