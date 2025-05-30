#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Idempotence" {
  prepare_sut "Starting idempotence tests in build directory ${BUILD_DIR}"

  step "Download DEMO database"
  assert_ahoy_download_db

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
