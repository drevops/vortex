#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran within a single test.
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

load _helper
load _helper_drevops
load _helper_drevops_workflow

@test "Workflow: DB-driven, custom webroot" {
  prepare_sut "Starting DB-driven WORKFLOW tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}" "rootdoc"

  assert_ahoy_download_db

  assert_ahoy_build "rootdoc"
  assert_gitignore "" "rootdoc"

  assert_ahoy_cli

  assert_env_changes

  assert_ahoy_composer

  assert_ahoy_drush

  assert_ahoy_info "rootdoc"

  assert_ahoy_docker_logs

  assert_ahoy_login

  assert_ahoy_export_db

  assert_ahoy_lint "rootdoc"

  assert_ahoy_test_unit "rootdoc"

  assert_ahoy_test_kernel "rootdoc"

  assert_ahoy_test_functional "rootdoc"

  assert_ahoy_test_bdd

  assert_ahoy_fei "rootdoc"

  assert_ahoy_fe "rootdoc"

  assert_ahoy_debug

  assert_ahoy_clean "rootdoc"

  assert_ahoy_reset "rootdoc"
}

@test "Workflow: download from image, storage in docker image" {
  # Do not use demo database - testing demo database discovery is another test.
  export DREVOPS_INSTALL_DEMO_SKIP=1

  export DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x
  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DREVOPS_DOCKER_REGISTRY_USERNAME=
  export DREVOPS_DOCKER_REGISTRY_TOKEN=

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
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x"
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
  # Do not use demo database - testing demo database discovery is another test.
  export DREVOPS_INSTALL_DEMO_SKIP=1

  export DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x
  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DREVOPS_DOCKER_REGISTRY_USERNAME=
  export DREVOPS_DOCKER_REGISTRY_TOKEN=

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
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x"
  # Assert that demo config was removed as a part of the installation.
  assert_file_not_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
  assert_file_not_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="

  assert_ahoy_build

  substep "Remove any existing or previously downloaded DB image dumps."
  rm -Rf .data/db.tar
  assert_file_not_exists .data/db.tar

  step "Update DB content"
  # Make a change to current site, export the DB image, remove existing DB image
  # and rebuild the stack - the used image should have the expected changes.
  substep "Assert that used DB image has content."
  assert_page_contains "/" "test database Docker image"

  substep "Change homepage content and assert that the change was applied."
  ahoy drush config-set system.site page.front /user -y
  assert_page_not_contains "/" "test database Docker image"
  assert_page_contains "/" "Username"

  substep "Exporting DB image to a file"
  ahoy export-db "db.tar"
  assert_file_exists .data/db.tar

  substep "Remove existing image and assert that exported DB image file still exists."
  ahoy clean
  docker_remove_image "${DREVOPS_DB_DOCKER_IMAGE}"
  assert_file_exists .data/db.tar

  substep "Re-run build to use previously exported DB image from file."
  assert_ahoy_build
  # Assert that remote image is not used.
  # This may fail if local image is not the same architecture as what is used
  # for Docker build.
  assert_output_not_contains "[auth]"

  substep "Assert that the contents of the DB was loaded from the exported DB image file."
  assert_page_not_contains "/" "test database Docker image"
  assert_page_contains "/" "Username"
  ahoy clean
}

@test "Workflow: download from curl, storage in Docker image" {
  export DREVOPS_DB_DOWNLOAD_SOURCE=curl

  # While the DB will be loaded from the file, the DB image must exist
  # so that Docker Compose could start a container, so the image should be
  # a real image.
  # @todo: build.sh may need to have a support to create a local image if
  # it does not exist.
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x
  # Explicitly specify that we do not want to login into the public registry
  # to use test image.
  export DREVOPS_DOCKER_REGISTRY_USERNAME=
  export DREVOPS_DOCKER_REGISTRY_TOKEN=

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
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-test-9.x"
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
