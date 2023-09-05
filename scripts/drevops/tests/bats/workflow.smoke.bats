#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Idempotence" {
  prepare_sut "Starting idempotence tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  step "Download DEMO database"
  assert_ahoy_download_db

  # Be verbose just for these smoke tests.
  export DREVOPS_DOCKER_VERBOSE=1
  export DREVOPS_COMPOSER_VERBOSE=1
  export DREVOPS_NPM_VERBOSE=1

  step "Build project"
  assert_ahoy_build
  assert_gitignore
  assert_ahoy_test_bdd_fast

  # Running build several times should result in the same project build results.
  step "Re-build project"
  assert_ahoy_build
  # Skip committing of the files.
  assert_gitignore 1
  assert_ahoy_test_bdd_fast
}

# Make sure to run with `TEST_GITHUB_TOKEN=working_test_token bats...` or this test will fail.
@test "GitHub token" {
  prepare_sut "Starting GitHub token tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  step "Add private package"
  rm composer.lock || true
  composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git
  jq --indent 4 '.require += {"drevops/test-private-package": "^1"}' composer.json >composer.json.tmp && mv -f composer.json.tmp composer.json

  step "Build without a GITHUB_TOKEN token"
  unset GITHUB_TOKEN
  run ahoy build
  assert_failure

  step "Build with a GITHUB_TOKEN token"
  export GITHUB_TOKEN="${TEST_GITHUB_TOKEN}"
  run ahoy build
  assert_success
}
