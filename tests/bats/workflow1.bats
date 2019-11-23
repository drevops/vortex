#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran withing a single test.

load _helper
load _helper_drevops
load _helper_drevops_workflow

@test "Workflow: fresh install" {
  prepare_sut "Starting fresh install WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # If there is no database downloaded, fresh install from profile is performed.
  assert_file_not_exists .data/db.sql

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_cli

  assert_ahoy_drush

  assert_ahoy_info

  assert_ahoy_docker_logs

  assert_ahoy_login

  assert_ahoy_export_db

  assert_ahoy_lint

  assert_ahoy_test_unit

  assert_ahoy_test_kernel

  assert_ahoy_test_functional

  assert_ahoy_test_bdd

  assert_ahoy_fe

  assert_export_on_install_site

  assert_ahoy_debug

  assert_ahoy_clean

  assert_ahoy_reset
}
