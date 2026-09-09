#!/usr/bin/env bats
##
# Unit tests for the Acquia database copy hook.
#
# A run ends in a 'pushd' into the Acquia site directory, which no test host
# has, so these tests cover the arguments, the skip flag and the required value
# guards that run before it.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the values a run requires so that a test can invalidate exactly one.
fixture_variables() {
  unset VORTEX_TASK_COPY_DB_ACQUIA_SKIP
  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_COPY_DB_ACQUIA_NAME="test-db"
}

# Runs the hook and asserts that a guard stopped it.
#
# Arguments:
#   1. name: Variable the guard reports.
assert_guard() {
  run ./hooks/library/copy-db.sh star_wars dev
  assert_failure
  assert_output_contains "${1}: Missing required value."
}

@test "hook-copy-db: missing site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/copy-db.sh
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: empty site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/copy-db.sh "" dev
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: missing target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/copy-db.sh star_wars
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: empty target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/copy-db.sh star_wars ""
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_TASK_COPY_DB_ACQUIA_SKIP=1

  run ./hooks/library/copy-db.sh star_wars dev
  assert_success
  assert_output_contains "Skipping copying of database between Acquia environments."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: skip flag other than 1" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_TASK_COPY_DB_ACQUIA_SKIP=0

  run ./hooks/library/copy-db.sh star_wars dev
  assert_failure
  assert_output_not_contains "Skipping copying of database between Acquia environments."

  popd >/dev/null || exit 1
}

@test "hook-copy-db: unset key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset VORTEX_ACQUIA_KEY

  assert_guard "VORTEX_ACQUIA_KEY"

  popd >/dev/null || exit 1
}

@test "hook-copy-db: empty key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_ACQUIA_KEY=""

  assert_guard "VORTEX_ACQUIA_KEY"

  popd >/dev/null || exit 1
}

@test "hook-copy-db: unset secret" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset VORTEX_ACQUIA_SECRET

  assert_guard "VORTEX_ACQUIA_SECRET"

  popd >/dev/null || exit 1
}

@test "hook-copy-db: empty secret" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_ACQUIA_SECRET=""

  assert_guard "VORTEX_ACQUIA_SECRET"

  popd >/dev/null || exit 1
}

@test "hook-copy-db: unset database name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset VORTEX_TASK_COPY_DB_ACQUIA_NAME

  assert_guard "VORTEX_TASK_COPY_DB_ACQUIA_NAME"

  popd >/dev/null || exit 1
}

@test "hook-copy-db: empty database name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_TASK_COPY_DB_ACQUIA_NAME=""

  assert_guard "VORTEX_TASK_COPY_DB_ACQUIA_NAME"

  popd >/dev/null || exit 1
}
