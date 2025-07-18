#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.workflow.bash

@test "Workflow: DB-driven" {
  prepare_sut "Starting DB-driven WORKFLOW tests in build directory ${BUILD_DIR}"

  assert_ahoy_download_db

  assert_ahoy_build
  assert_gitignore

  assert_solr

  assert_ahoy_cli

  assert_env_changes

  assert_timezone

  assert_ahoy_composer

  assert_ahoy_drush

  assert_ahoy_info

  assert_ahoy_container_logs

  assert_ahoy_login

  # Export to default file.
  assert_ahoy_export_db

  # Export to custom file.
  assert_ahoy_export_db "mydb.sql"

  # Import from default file.
  assert_ahoy_import_db

  # Import from custom file.
  assert_ahoy_import_db "mydb.sql"

  assert_ahoy_lint

  assert_ahoy_test

  assert_ahoy_fei

  assert_ahoy_fe

  assert_ahoy_debug

  # Run this test as a last one to make sure that there is no concurrency issues
  # with enabled Valkey.
  assert_valkey

  assert_ahoy_reset

  assert_ahoy_reset_hard
}

@test "Workflow: DB-driven, provision" {
  prepare_sut "Starting DB-driven, provision WORKFLOW tests in build directory ${BUILD_DIR}"

  assert_ahoy_download_db
  assert_ahoy_build

  assert_ahoy_provision
}

@test "Workflow: Build Docker Compose stack with frontend build" {
  export VORTEX_DEV_VOLUMES_SKIP_MOUNT=1
  export COMPOSE_PROJECT_NAME="test_frontend_build_$$_$RANDOM"

  prepare_sut "Starting DB-driven, with frontend build WORKFLOW tests in build directory ${BUILD_DIR}"

  local webroot="web"
  rm -Rf "${webroot}/themes/custom/star_wars/build"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules"

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building CLI image"
  run docker compose build --no-cache cli
  assert_success

  substep "Start temporary container and copy files"
  run docker compose up -d --no-build cli
  assert_success
  run docker compose cp cli:/app/. .
  assert_success
  run docker compose down
  assert_success

  substep "Assert frontend build assets exist after build"
  assert_dir_exists "${webroot}/themes/custom/star_wars/build"

  run docker compose down --remove-orphans
  assert_success
}


@test "Workflow: Build Docker Compose stack without frontend build" {
  export VORTEX_DEV_VOLUMES_SKIP_MOUNT=1
  export COMPOSE_PROJECT_NAME="test_frontend_build_$$_$RANDOM"

  prepare_sut "Starting DB-driven, with frontend build WORKFLOW tests in build directory ${BUILD_DIR}"

  export VORTEX_FRONTEND_BUILD_SKIP=1

  local webroot="web"
  rm -Rf "${webroot}/themes/custom/star_wars/build"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules"

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building CLI image"
  run docker compose build --no-cache cli
  assert_success

  substep "Start temporary container and copy files"
  run docker compose up -d --no-build cli
  assert_success
  run docker compose cp cli:/app/. .
  assert_success
  run docker compose down
  assert_success

  substep "Assert frontend build not assets exist after build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  run docker compose down --remove-orphans
  assert_success
}
