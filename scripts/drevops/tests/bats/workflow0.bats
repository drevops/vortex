#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran withing a single test.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper_workflow.bash

@test "Workflow: DB-driven" {
  prepare_sut "Starting DB-driven WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  assert_ahoy_download_db

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_cli

  assert_env_changes

  assert_ahoy_composer

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

  assert_ahoy_fei

  assert_ahoy_fe

  assert_ahoy_debug

  assert_ahoy_clean

  assert_ahoy_reset
}

@test "Workflow: profile-driven" {
  rm -f .data/db.sql
  export DREVOPS_INSTALL_DEMO_SKIP=1
  assert_file_not_exists .data/db.sql

  prepare_sut "Starting fresh install WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"
  # Assert that the database was not downloaded because DREVOPS_INSTALL_DEMO_SKIP was set.
  assert_file_not_exists .data/db.sql

  echo "DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1" >> .env

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_cli

  assert_ahoy_composer

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

  assert_ahoy_debug

  assert_ahoy_clean

  assert_ahoy_reset
}

@test "Idempotence" {
  prepare_sut "Starting idempotence tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # Assert that DEMO database is downloaded.
  assert_ahoy_download_db

  assert_ahoy_build
  assert_gitignore
  assert_ahoy_test_bdd

  # Running build several times should result in the same project build results.
  assert_ahoy_build
  # Skip committing of the files.
  assert_gitignore 1
  assert_ahoy_test_bdd
}

@test "Utilities" {
  prepare_sut "Starting utilities tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  assert_ahoy_local

  assert_ahoy_doctor_info

  assert_ahoy_github_labels
}
