#!/usr/bin/env bats
#
# Utilities.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper_workflow.bash

@test "Utilities" {
  prepare_sut "Starting utilities tests for Drupal ${DREVOPS_DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  assert_ahoy_local

  assert_ahoy_doctor_info

  assert_ahoy_github_labels
}
