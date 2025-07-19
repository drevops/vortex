#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.workflow.bash
#
#@test "Workflow: DB-driven" {
#  prepare_sut "Starting DB-driven WORKFLOW tests in build directory ${BUILD_DIR}"
#
#  assert_ahoy_download_db
#
#  assert_ahoy_build
#  assert_gitignore
#
#  assert_solr
#
#  assert_ahoy_cli
#
#  assert_env_changes
#
#  assert_timezone
#
#  assert_ahoy_composer
#
#  assert_ahoy_drush
#
#  assert_ahoy_info
#
#  assert_ahoy_container_logs
#
#  assert_ahoy_login
#
#  # Export to default file.
#  assert_ahoy_export_db
#
#  # Export to custom file.
#  assert_ahoy_export_db "mydb.sql"
#
#  # Import from default file.
#  assert_ahoy_import_db
#
#  # Import from custom file.
#  assert_ahoy_import_db "mydb.sql"
#
#  assert_ahoy_lint
#
#  assert_ahoy_test
#
#  assert_ahoy_fei
#
#  assert_ahoy_fe
#
#  assert_ahoy_debug
#
#  # Run this test as a last one to make sure that there is no concurrency issues
#  # with enabled Valkey.
#  assert_valkey
#
#  assert_ahoy_reset
#
#  assert_ahoy_reset_hard
#}
#
#@test "Workflow: DB-driven, provision" {
#  prepare_sut "Starting DB-driven, provision WORKFLOW tests in build directory ${BUILD_DIR}"
#
#  assert_ahoy_download_db
#  assert_ahoy_build
#
#  assert_ahoy_provision
#}

@test "Workflow: Build Docker Compose stack" {
  export VORTEX_DEV_VOLUMES_SKIP_MOUNT=1

  prepare_sut "Starting DB-driven, no frontend build WORKFLOW tests in build directory ${BUILD_DIR}"

  export VORTEX_DB_DOWNLOAD_URL="${VORTEX_INSTALL_DEMO_DB_TEST}"
  export VORTEX_CONTAINER_REGISTRY_USER="${TEST_VORTEX_CONTAINER_REGISTRY_USER?Test container registry user is not set}"
  export VORTEX_CONTAINER_REGISTRY_PASS="${TEST_VORTEX_CONTAINER_REGISTRY_PASS?Test container registry pass is not set}"
  local webroot="web"

  export VORTEX_FRONTEND_BUILD_SKIP=0

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building stack"
  run docker compose build --no-cache
  assert_success
  run docker compose up -d --force-recreate
  assert_success
  run sync_to_host

  substep "Assert frontend build assets exist after build"
  assert_dir_exists "${webroot}/themes/custom/star_wars/build"

  rm -Rf "${webroot}/themes/custom/star_wars/build"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules"

  export VORTEX_FRONTEND_BUILD_SKIP=1

  substep "Assert frontend build assets do not exist before build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"

  substep "Building stack without frontend build"
  process_ahoyyml
  run docker compose build --no-cache
  assert_success
  run docker compose up -d --force-recreate
  assert_success
  sleep 2
  run sync_to_host

  substep "Assert frontend build assets do not exist after build"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/build"
}
