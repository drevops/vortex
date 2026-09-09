#!/usr/bin/env bats
##
# Unit tests for the Acquia cache purge hook.
#
# A run ends in a 'pushd' into the Acquia site directory, which no test host
# has, so these tests cover the arguments, the skip flag and the required value
# guards that run before it.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the values a run requires so that a test can invalidate exactly one.
fixture_variables() {
  unset VORTEX_PURGE_CACHE_ACQUIA_SKIP
  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
}

# Runs the hook and asserts that a guard stopped it.
#
# Arguments:
#   1. name: Variable the guard reports.
assert_guard() {
  run ./hooks/library/purge-cache.sh star_wars dev
  assert_failure
  assert_output_contains "${1}: Missing required value."
}

@test "hook-purge-cache: missing site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/purge-cache.sh
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: empty site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/purge-cache.sh "" dev
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: missing target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/purge-cache.sh star_wars
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: empty target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/purge-cache.sh star_wars ""
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_PURGE_CACHE_ACQUIA_SKIP=1

  run ./hooks/library/purge-cache.sh star_wars dev
  assert_success
  assert_output_contains "Skipping purging of cache in Acquia environment."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: skip flag other than 1" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_PURGE_CACHE_ACQUIA_SKIP=0

  run ./hooks/library/purge-cache.sh star_wars dev
  assert_failure
  assert_output_not_contains "Skipping purging of cache in Acquia environment."

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: unset key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset VORTEX_ACQUIA_KEY

  assert_guard "VORTEX_ACQUIA_KEY"

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: empty key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_ACQUIA_KEY=""

  assert_guard "VORTEX_ACQUIA_KEY"

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: unset secret" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset VORTEX_ACQUIA_SECRET

  assert_guard "VORTEX_ACQUIA_SECRET"

  popd >/dev/null || exit 1
}

@test "hook-purge-cache: empty secret" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_ACQUIA_SECRET=""

  assert_guard "VORTEX_ACQUIA_SECRET"

  popd >/dev/null || exit 1
}
