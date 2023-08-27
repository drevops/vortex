#!/usr/bin/env bats
#
# Workflows using different types of DB storage.
#
# Throughout these tests, a "drevops/drevops-mariadb-drupal-data-test-10.x:latest"
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
load _helper.workflow.bash

@test "Workflow: download from image, storage in docker image, use cached image" {
  # Note that output assertions in this test do not end with a dot on purpose
  # as different versions of Docker may produce different messages.

  # Force storage in docker image - the purpose of this test.
  export DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry

  # Use a test image. Image always must use a tag.
  export DREVOPS_DB_DOCKER_IMAGE="drevops/drevops-mariadb-drupal-data-test-10.x:latest"

  # Do not use demo database - testing demo database discovery is another test.
  export DREVOPS_INSTALL_DEMO_SKIP=1

  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DOCKER_USER=
  export DOCKER_PASS=

  substep "Make sure that demo database will not be downloaded."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  substep "Remove any existing images to download the fresh one."
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"

  prepare_sut "Starting download from image, storage in Docker image, use cached image WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # Assert that the database was not downloaded because DREVOPS_INSTALL_DEMO_SKIP was set.
  assert_file_not_exists .data/db.sql
  # Remove .env.local added by the installer script.
  rm .env.local >/dev/null

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry"
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=${DREVOPS_DB_DOCKER_IMAGE}"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-10.x:latest"
  assert_file_not_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="

  step "Initial build to use data image."
  assert_ahoy_build
  assert_output_contains "Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Not found ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Not found archived database Docker image file ./.data/db.tar."
  assert_output_contains "Finished building project"

  substep "Remove any existing or previously downloaded DB image dumps."
  rm -Rf .data/db.tar
  assert_file_not_exists .data/db.tar

  step "Update DB content"
  # Make a change to current site, export the DB image, remove existing DB image
  # and rebuild the stack - the used image should have the expected changes.
  substep "Assert that used DB image has content."
  assert_webpage_contains "/" "test database Docker image"
  assert_webpage_not_contains "/" "Username"

  substep "Change homepage content and assert that the change was applied."
  ahoy drush config-set system.site page.front /user -y
  assert_webpage_not_contains "/" "test database Docker image"
  assert_webpage_contains "/" "Username"

  substep "Exporting DB image to a file"
  run ahoy export-db "db.tar"
  assert_success
  assert_output_contains "Found mariadb service container with id"
  assert_output_contains "Committing exported Docker image with name docker.io/${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Committed exported Docker image with id"
  assert_output_contains "Exporting database image archive to file ./.data/db.tar."
  assert_output_contains "Saved exported database image archive file ./.data/db.tar."
  assert_file_exists .data/db.tar

  substep "Remove existing image and assert that exported DB image file still exists."
  ahoy clean
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"
  assert_file_exists .data/db.tar

  step "Re-run build to use previously exported DB image from file."
  assert_ahoy_build
  assert_output_contains "Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Not found ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Found archived database Docker image file ./.data/db.tar. Expanding"
  assert_output_contains "Loaded image: ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Found expanded ${DREVOPS_DB_DOCKER_IMAGE}"

  assert_output_contains "Finished building project"

  step "Assert that the contents of the DB was loaded from the exported DB image file."
  assert_webpage_not_contains "/" "test database Docker image"
  assert_webpage_contains "/" "Username"
  ahoy clean
}
