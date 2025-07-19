#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.workflow.bash

@test "Workflow: Build Docker Compose stack with frontend build" {
  export VORTEX_DEV_VOLUMES_SKIP_MOUNT=1

  prepare_sut "Starting DB-driven, with frontend build WORKFLOW tests in build directory ${BUILD_DIR}"

  local webroot="web"
  rm -Rf "${webroot}/themes/custom/star_wars/build"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules"

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building CLI image with frontend build"
  run docker compose build --no-cache cli
  assert_success

  substep "Copy files from built image to host"
  run docker create --name temp_cli "$(docker compose images -q cli)"
  assert_success
  run docker cp temp_cli:/app/. .
  assert_success
  run docker rm temp_cli
  assert_success

  substep "Assert frontend build assets exist after build"
  assert_dir_exists "${webroot}/themes/custom/star_wars/build"

  run docker compose down --remove-orphans
  assert_success
}

@test "Workflow: Build Docker Compose stack without frontend build" {
  export VORTEX_DEV_VOLUMES_SKIP_MOUNT=1

  prepare_sut "Starting DB-driven, no frontend build WORKFLOW tests in build directory ${BUILD_DIR}"

  export VORTEX_FRONTEND_BUILD_SKIP=1

  local webroot="web"
  rm -Rf "${webroot}/themes/custom/star_wars/build"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules"

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building CLI image without frontend build"
  run docker compose build --no-cache cli
  assert_success

  substep "Copy files from built image to host"
  run docker create --name temp_cli "$(docker compose images -q cli)"
  assert_success
  run docker cp temp_cli:/app/. .
  assert_success
  run docker rm temp_cli
  assert_success

  substep "Assert frontend build assets do not exist after build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  run docker compose down --remove-orphans
  assert_success
}
