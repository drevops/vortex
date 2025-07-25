#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.workflow.bash

@test "Workflow: DB-driven, provision" {
  prepare_sut "Starting DB-driven, provision WORKFLOW tests in build directory ${BUILD_DIR}"

  assert_ahoy_download_db
  assert_ahoy_build

  assert_ahoy_provision
}
