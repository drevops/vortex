#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran withing a single test.
#
# Throughout these tests, a "drevops/drevops-mariadb-drupal-data-test-7.x"
# test image is used: it is seeded with content from the pre-built fixture
# "Star wars" test site.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops
load _helper_drevops_workflow

#@test "Workflow: download from image, storage in docker image" {
#  # Do not use demo database - testing demo database discovery is another test.
#  export DREVOPS_SKIP_DEMO=1
#
#  export DATABASE_DOWNLOAD_SOURCE=docker_registry
#  export DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x
#  # Explicitly specify that we do not want to login into the public registry
#  # to use test image.
#  export DOCKER_REGISTRY_USERNAME=
#  export DOCKER_REGISTRY_TOKEN=
#
#  # Make sure that demo database will not be downloaded.
#  rm -f .data/db.sql
#  assert_file_not_exists .data/db.sql
#
#  # Remove any existing images to download the fresh one.
#  docker image rm "${DATABASE_IMAGE}" || true
#  docker image ls | grep -q -v "${DATABASE_IMAGE}"
#
#  prepare_sut "Starting download from image, storage in docker image WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"
#  # Assert that the database was not downloaded because DREVOPS_SKIP_DEMO was set.
#  assert_file_not_exists .data/db.sql
#
#  assert_file_contains ".env" "DATABASE_DOWNLOAD_SOURCE=docker_registry"
#  assert_file_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x"
#  # Assert that demo config was removed as a part of the install.
#  assert_file_not_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-demo-7.x"
#  assert_file_not_contains ".env" "CURL_DB_URL="
#
#  assert_ahoy_build
#
#  # Assert that DB reload would revert the content.
#  assert_reload_db_image
#
#  # Other stack asserts.
#  assert_gitignore
#
#  assert_ahoy_cli
#
#  assert_ahoy_drush
#
#  assert_ahoy_info
#
#  assert_ahoy_docker_logs
#
#  assert_ahoy_login
#
#  assert_ahoy_lint
#
#  assert_ahoy_test_unit
#
#  assert_ahoy_test_bdd
#
#  assert_ahoy_fe
#
#  assert_ahoy_debug
#
#  assert_ahoy_export_db "mydb.tar"
#
#  assert_ahoy_clean
#
#  assert_ahoy_reset
#}
#
#@test "Workflow: download from image, storage in docker image, use cached image" {
#  # Do not use demo database - testing demo database discovery is another test.
#  export DREVOPS_SKIP_DEMO=1
#
#  export DATABASE_DOWNLOAD_SOURCE=docker_registry
#  export DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x
#  # Explicitly specify that we do not want to login into the public registry
#  # to use test image.
#  export DOCKER_REGISTRY_USERNAME=
#  export DOCKER_REGISTRY_TOKEN=
#
#  # Make sure that demo database will not be downloaded.
#  rm -f .data/db.sql
#  assert_file_not_exists .data/db.sql
#  # Remove any existing images to download the fresh one.
#  docker image rm "${DATABASE_IMAGE}" || true
#  docker image ls | grep -q -v "${DATABASE_IMAGE}"
#
#  prepare_sut "Starting download from image, storage in docker image WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"
#  # Assert that the database was not downloaded because DREVOPS_SKIP_DEMO was set.
#  assert_file_not_exists .data/db.sql
#
#  assert_file_contains ".env" "DATABASE_DOWNLOAD_SOURCE=docker_registry"
#  assert_file_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x"
#  # Assert that demo config was removed as a part of the install.
#  assert_file_not_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-demo-7.x"
#  assert_file_not_contains ".env" "CURL_DB_URL="
#
#  assert_ahoy_build
#
#  # Remove any previously downloaded DB dumps.
#  rm -Rf .data/db.tar
#  assert_file_not_exists .data/db.tar
#
#  substep "Update DB content"
#  # Make a change to current site, export the DB image, remove existing DB image
#  # and rebuild the stack - the used image should have the expected changes.
#  #
#  # Assert that used DB image has content.
#  assert_page_contains "/" "First test node"
#  # Change homepage content and assert that the change was applied.
#  ahoy drush vset site_frontpage user
#  assert_page_not_contains "/" "First test node"
#  assert_page_contains "/" "Username"
#
#  substep "Exporting DB image"
#  ahoy export-db "db.tar"
#  assert_file_exists .data/db.tar
#
#  substep "Remove existing image and assert that exported image still exists."
#  ahoy clean
#  docker image rm "${DATABASE_IMAGE}" || true
#  docker image ls | grep -q -v "${DATABASE_IMAGE}"
#  assert_file_exists .data/db.tar
#
#  substep "Re-run build to use  DB image"
#  assert_ahoy_build
#
#  substep "Assert that the contents of the DB was loaded from the archive"
#  assert_page_not_contains "/" "First test node"
#  assert_page_contains "/" "Username"
#  ahoy clean
#}

@test "Workflow: download from curl, storage in docker image" {
  export DATABASE_DOWNLOAD_SOURCE=curl

  # While the DB will be loaded from the file, the DB image must exist
  # so that Docker Compose could start a container, so the image should be
  # a real image.
  # @todo: build.sh may need to have a support to create a local image if
  # it does not exist.
  export DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x
  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DOCKER_REGISTRY_USERNAME=
  export DOCKER_REGISTRY_TOKEN=

  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql

  # Remove any existing images to download the fresh one.
  docker image rm "${DATABASE_IMAGE}" || true
  docker image ls | grep -q -v "${DATABASE_IMAGE}"

  prepare_sut "Starting download from curl, storage in docker image WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"
  assert_file_exists .data/db.sql

  assert_file_contains ".env" "DATABASE_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-test-7.x"
  # Assert that demo config was removed as a part of the install.
  assert_file_not_contains ".env" "DATABASE_IMAGE=drevops/drevops-mariadb-drupal-data-demo-7.x"
  assert_file_contains ".env" "CURL_DB_URL="

  assert_ahoy_build

  # We need to test 2 cases:
  # 1. Site is built from DB file, site reloaded from image, while DB file exists.
  #    This will result in the site install from the file again.
  # 2. Site is built from DB file, site reloaded from image, while DB file does not exist.
  #    This will result in the site install from the image.

  substep "Case 1: Site is built from DB file, site reloaded from image, while DB file exists."
  assert_page_contains "/" "Welcome"
  assert_page_not_contains "/" "First test node"
  # Set content to a different path.
  ahoy drush vset site_frontpage user -y
  assert_page_not_contains "/" "Welcome"
  ahoy reload-db
  assert_page_contains "/" "Welcome"
  assert_page_not_contains "/" "First test node"

  substep "Case 2: Site is built from DB file, site reloaded from image, while DB file does not exist."
  rm -f .data/db.sql
  assert_file_not_exists .data/db.sql
  ahoy reload-db
  # @todo: Fix this assertion. The site is still shows as installed from DB file.
  # assert_page_contains "/" "First test node"

  # Other stack asserts.
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
