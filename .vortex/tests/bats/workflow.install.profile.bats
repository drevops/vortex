#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Workflow: profile-driven" {
  rm -f .data/db.sql
  export VORTEX_INSTALL_DEMO_SKIP=1
  assert_file_not_exists .data/db.sql

  prepare_sut "Starting fresh install WORKFLOW tests in build directory ${BUILD_DIR}"
  # Assert that the database was not downloaded because VORTEX_INSTALL_DEMO_SKIP was set.
  assert_file_not_exists .data/db.sql

  echo "VORTEX_PROVISION_TYPE=profile" >>.env

  assert_ahoy_build
  assert_gitignore

  assert_ahoy_lint

  assert_ahoy_test "web" "1"

  assert_ahoy_fe
}
