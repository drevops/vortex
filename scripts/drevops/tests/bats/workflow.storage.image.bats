#!/usr/bin/env bats
#
# Workflows using different types of DB storage.
#
# Throughout these tests, a "drevops/drevops-mariadb-drupal-data-test-9.x"
# test image is used: it is seeded with content from the pre-built fixture
# "Star wars" test site.
#
# When debugging failed tests locally, make sure that there are no untagged
# "drevops/drevops-mariadb-drupal-data-*" images.
#
# In some cases, shell may report platform incorrectly. Run with forced platform:
# DOCKER_DEFAULT_PLATFORM=linux/amd64 bats --tap tests/bats/workflow1.bats
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper_workflow.bash

# Due to test speed efficiency, all workflow assertions ran within a single test.
@test "Workflow: download from image, storage in docker image" {
  # Force storage in docker image - the purpose of this test.
  export DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry

  # Use a test image. Image always must use a tag.
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x:latest

  # Do not use demo database - testing demo database discovery is another test.
  export DREVOPS_INSTALL_DEMO_SKIP=1

  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DOCKER_USERNAME=
  export DOCKER_PASS=

  substep "Make sure that demo database will not be used."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  substep "Remove any existing images to download the fresh one."
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"

  prepare_sut "Starting download from image, storage in docker image WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # Assert that the database was not downloaded because DREVOPS_INSTALL_DEMO_SKIP was set.
  assert_file_not_exists .data/db.sql
  # Remove .env.local added by the installer script.
  rm .env.local > /dev/null

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry"
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=${DREVOPS_DB_DOCKER_IMAGE}"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
  assert_file_not_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="

  assert_ahoy_build

  # Assert that DB reload would revert the content.
  step "Reload DB image"

  # Assert that used DB image has content.
  assert_webpage_contains "/" "test database Docker image"

  # Change homepage content and assert that the change was applied.
  ahoy drush config-set system.site page.front /user -y
  assert_webpage_not_contains "/" "test database Docker image"

  ahoy reload-db
  assert_webpage_contains "/" "test database Docker image"

  # Other stack assertions - these run only for this Docker image-related test.
  assert_gitignore

  assert_ahoy_info "web" "${DREVOPS_DB_DOCKER_IMAGE}"

  assert_ahoy_docker_logs

  assert_ahoy_login

  assert_ahoy_debug

  assert_ahoy_export_db "mydb.tar"

  assert_ahoy_clean

  assert_ahoy_reset
}
