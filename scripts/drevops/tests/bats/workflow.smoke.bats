#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper_workflow.bash

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

@test "GitHub token" {
  prepare_sut "Starting GitHub token tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  step "Add private package"
  rm composer.lock || true
  composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git
  jq --indent 4 '.require += {"drevops/test-private-package": "^1"}' composer.json > composer.json.tmp && mv -f composer.json.tmp composer.json

  step "Run build without a token"
  unset GITHUB_TOKEN
  run ahoy build
  assert_failure

  step "Run build with a token"
  export GITHUB_TOKEN="${TEST_GITHUB_TOKEN}"
  run ahoy build
  assert_success
}
