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
  assert_reload_db_image

  # Other stack assertions - these run only for this Docker image-related test.
  assert_gitignore

  assert_ahoy_cli

  assert_ahoy_drush

  assert_ahoy_info

  assert_ahoy_docker_logs

  assert_ahoy_login

  assert_ahoy_lint

  assert_ahoy_test_unit

  assert_ahoy_test_bdd

  assert_ahoy_fe

  assert_ahoy_debug

  assert_ahoy_export_db "mydb.tar"

  assert_ahoy_clean

  assert_ahoy_reset
}

@test "Workflow: download from image, storage in docker image, use cached image" {
  # Note that output assertions in this test do not end with a dot on purpose
  # as different versions of Docker may produce different messages.

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

  substep "Make sure that demo database will not be downloaded."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  substep "Remove any existing images to download the fresh one."
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"

  prepare_sut "Starting download from image, storage in Docker image WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # Assert that the database was not downloaded because DREVOPS_INSTALL_DEMO_SKIP was set.
  assert_file_not_exists .data/db.sql
  # Remove .env.local added by the installer script.
  rm .env.local > /dev/null

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry"
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=${DREVOPS_DB_DOCKER_IMAGE}"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
  assert_file_not_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="

  step "Initial build to use data image."
  assert_ahoy_build
  assert_output_contains "Using Docker data image ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Not found ${DREVOPS_DB_DOCKER_IMAGE}"
  assert_output_contains "Not found archived database Docker image file ./.data/db.tar."
  assert_output_contains "Build complete "

  substep "Remove any existing or previously downloaded DB image dumps."
  rm -Rf .data/db.tar
  assert_file_not_exists .data/db.tar

  step "Update DB content"
  # Make a change to current site, export the DB image, remove existing DB image
  # and rebuild the stack - the used image should have the expected changes.
  substep "Assert that used DB image has content."
  assert_page_contains "/" "test database Docker image"
  assert_page_not_contains "/" "Username"

  substep "Change homepage content and assert that the change was applied."
  ahoy drush config-set system.site page.front /user -y
  assert_page_not_contains "/" "test database Docker image"
  assert_page_contains "/" "Username"

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

  assert_output_contains "Build complete "

  step "Assert that the contents of the DB was loaded from the exported DB image file."
  assert_page_not_contains "/" "test database Docker image"
  assert_page_contains "/" "Username"
  ahoy clean
}

@test "Workflow: download from curl, storage in Docker image" {
  # Force storage in DB dump - the purpose of this test.
  export DREVOPS_DB_DOWNLOAD_SOURCE=curl

  # While the DB will be loaded from the file, the DB image must exist
  # so that Docker Compose could start a container, so the image should be
  # a real image.
  # @todo: build.sh may need to have a support to create a local image if
  # it does not exist.
  # Use a test image. Image always must use a tag.
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x:latest

  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DOCKER_USERNAME=
  export DOCKER_PASS=

  # Mimic local behavior where DB is always overridden.
  export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=1

  substep "Make sure that demo database will not be downloaded."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  substep "Remove any existing images to download the fresh one."
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"

  prepare_sut "Starting download from curl, storage in Docker image cached WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"
  assert_file_exists .data/db.sql

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=${DREVOPS_DB_DOCKER_IMAGE}"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="

  assert_ahoy_build

  # We need to test 2 cases:
  # 1. Site is built from DB file, site reloaded from image, while DB file exists.
  #    This will result in the site install from the file again.
  # 2. Site is built from DB file, site reloaded from image, while DB file does not exist.
  #    This will result in the site install from the image.

  step "Case 1: Site is built from DB file, site reloaded from image, while DB file exists."
  substep "Assert that the text is from the DB dump."
  assert_page_contains "/" "test database dump"

  substep "Set content to a different path."
  ahoy drush config-set system.site page.front /user -y
  assert_page_not_contains "/" "test database dump"

  substep "Reloading DB from image with DB dump file present"
  assert_file_exists .data/db.sql
  run ahoy reload-db
  assert_success

  substep "Assert that the text is from the DB dump after reload."
  assert_page_contains "/" "test database dump"

  # Skipped: the drupal-install-site.sh expects DB dump file to exist; this logic
  # needs to be refactored. Currently, reloading without DB dump file present
  # will fail the build.
  #
  # step "Case 2: Site is built from DB file, site reloaded from image, while DB file does not exist."
  # rm -f .data/db.sql
  # assert_file_not_exists .data/db.sql
  # run ahoy reload-db
  # assert_success
  #
  # substep "Assert that the text is from the Docker image."
  # assert_page_contains "/" "test database Docker image"

  assert_ahoy_export_db "mydb.tar"
}
