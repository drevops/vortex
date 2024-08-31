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

@test "Workflow: download from curl, storage in container image" {
  # Force storage in DB dump - the purpose of this test.
  export VORTEX_DB_DOWNLOAD_SOURCE=curl

  # While the DB will be loaded from the file, the DB image must exist
  # so that Docker Compose could start a container, so the image should be
  # a real image.
  # @todo: build.sh may need to have a support to create a local image if
  # it does not exist.
  # Use a test image. Image always must use a tag.
  export VORTEX_DB_IMAGE="drevops/drevops-mariadb-drupal-data-test-10.x:latest"

  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export VORTEX_CONTAINER_REGISTRY_USER=
  export VORTEX_CONTAINER_REGISTRY_PASS=

  # Mimic local behavior where DB is always overridden.
  export VORTEX_PROVISION_OVERRIDE_DB=1

  substep "Make sure that demo database will not be downloaded."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  substep "Remove any existing images to download the fresh one."
  docker_remove_image "${VORTEX_DB_IMAGE}"

  prepare_sut "Starting download from curl, storage in container image cached WORKFLOW tests in build directory ${BUILD_DIR}"
  assert_file_exists .data/db.sql

  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "VORTEX_DB_IMAGE=${VORTEX_DB_IMAGE}"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "VORTEX_DB_IMAGE=drevops/drevops-mariadb-drupal-data-demo-10.x:latest"
  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_CURL_URL="

  assert_ahoy_build

  assert_ahoy_test_bdd_fast

  # We need to test 2 cases:
  # 1. Site is built from DB file, site reloaded from image, while DB file exists.
  #    This will result in the site install from the file again.
  # 2. Site is built from DB file, site reloaded from image, while DB file does not exist.
  #    This will result in the site install from the image.

  step "Case 1: Site is built from DB file, site reloaded from image, while DB file exists."
  substep "Assert that the text is from the DB dump."
  assert_webpage_contains "/" "test database dump"

  substep "Set content to a different path."
  ahoy drush config-set system.site page.front /user -y
  assert_webpage_not_contains "/" "test database dump"

  substep "Reloading DB from image with DB dump file present"
  assert_file_exists .data/db.sql
  run ahoy reload-db
  assert_success

  substep "Assert that the text is from the DB dump after reload."
  assert_webpage_contains "/" "test database dump"

  # Skipped: the provision.sh expects DB dump file to exist; this logic
  # needs to be refactored. Currently, reloading without DB dump file present
  # will fail the build.
  #
  # step "Case 2: Site is built from DB file, site reloaded from image, while DB file does not exist."
  # rm -f .data/db.sql
  # assert_file_not_exists .data/db.sql
  # run ahoy reload-db
  # assert_success
  #
  # substep "Assert that the text is from the container image."
  # assert_page_contains "/" "test database Docker image"

  assert_ahoy_export_db "mydb.tar"
}
