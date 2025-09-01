#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.workflow.bash

@test "Workflow: profile-driven, Drupal CMS" {
  rm -f .data/db.sql
  export VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP=1
  assert_file_not_exists .data/db.sql

  export VORTEX_INSTALLER_PROMPT_STARTER="install_profile_drupalcms"

  prepare_sut "Starting fresh install WORKFLOW tests in build directory ${BUILD_DIR}"
  # Assert that the database was not downloaded because VORTEX_INSTALLER_IS_DEMO_DB_DOWNLOAD_SKIP was set.
  assert_file_not_exists .data/db.sql

  assert_file_contains .env "DRUPAL_PROFILE=../recipes/drupal_cms_starter"
  assert_file_not_contains .env "DEMO"
  assert_file_contains composer.json "drupal/cms"
  assert_file_contains composer.json "wikimedia/composer-merge-plugin"
  assert_file_contains composer.json "vendor/drupal/cms/composer.json"

  echo "VORTEX_PROVISION_TYPE=profile" >>.env

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_lint

  assert_ahoy_test "web" "1"

  assert_ahoy_fe

  assert_webpage_contains "/" "This is the home page of your new site."
}
