#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran withing a single test.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops
load _helper_drevops_workflow

@test "Workflow: DB-driven" {
  prepare_sut "Starting DB-driven WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  assert_ahoy_download_db

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_cli

  assert_env_changes

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

  assert_export_on_install_site

  assert_ahoy_debug

  assert_ahoy_clean

  assert_ahoy_reset
}

@test "Workflow: fresh install automatically discovered if database does not exist" {
  rm -f .data/db.sql
  export DREVOPS_SKIP_DEMO=1
  assert_file_not_exists .data/db.sql

  prepare_sut "Starting fresh install WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"
  # Assert that the database was not downloaded because DREVOPS_SKIP_DEMO was set.
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

@test "Workflow: fresh install forced if FORCE_FRESH_INSTALL=1 and site already exists" {
  rm -f .data/db.sql
  export DREVOPS_SKIP_DEMO=1
  assert_file_not_exists .data/db.sql

  prepare_sut "Starting fresh install WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"
  # Assert that the database was not downloaded because DREVOPS_SKIP_DEMO was set.
  assert_file_not_exists .data/db.sql

  substep "Build the project with default settings"
  assert_ahoy_build
  assert_gitignore
  assert_ahoy_info
  assert_ahoy_login

  # Assert that after default installation the page does not contain text.
  assert_page_contains "/" "Welcome"

  substep "Change site content"
  ahoy drush config-set system.site page.front /user -y
  assert_page_not_contains "/" "Welcome"

  substep "Re-install site"
  ahoy install-site >&3

  substep "Assert content was not removed after re-install"
  assert_page_not_contains "/" "Welcome"

  substep "Add FORCE_FRESH_INSTALL variable and re-install site"
  # Add variable to the .env file and apply the change to container.
  add_var_to_file .env "FORCE_FRESH_INSTALL" "1"
  ahoy up cli
  sync_to_container

  substep "Re-install site"
  ahoy install-site

  substep "Assert site was fully re-installed"
  assert_page_contains "/" "Welcome"
}

@test "Idempotence" {
  prepare_sut "Starting idempotence tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

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
