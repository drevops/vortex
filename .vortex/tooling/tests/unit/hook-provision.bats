#!/usr/bin/env bats
##
# Unit tests for the Acquia provision hook.
#
# A run ends in a 'pushd' into the Acquia site directory, which no test host
# has, so these tests cover the arguments and the skip flag that run before it.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the values a run requires so that a test can invalidate exactly one.
fixture_variables() {
  unset VORTEX_PROVISION_ACQUIA_SKIP
}

@test "hook-provision: missing site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/provision.sh
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-provision: empty site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/provision.sh "" dev
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-provision: missing target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/provision.sh star_wars
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-provision: empty target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/provision.sh star_wars ""
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-provision: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_PROVISION_ACQUIA_SKIP=1

  run ./hooks/library/provision.sh star_wars dev
  assert_success
  assert_output_contains "Skipping provisioning of the site in Acquia environment."

  popd >/dev/null || exit 1
}

@test "hook-provision: skip flag other than 1" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_PROVISION_ACQUIA_SKIP=0

  run ./hooks/library/provision.sh star_wars dev
  assert_failure
  assert_output_not_contains "Skipping provisioning of the site in Acquia environment."

  popd >/dev/null || exit 1
}
