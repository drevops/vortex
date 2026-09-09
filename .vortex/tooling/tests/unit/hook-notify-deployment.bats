#!/usr/bin/env bats
##
# Unit tests for the Acquia deployment notification hook.
#
# A run ends in a 'pushd' into the Acquia site directory, which no test host
# has, so these tests cover the arguments, the skip flag and the environment URL
# assembly that run before it.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the values a run requires so that a test can invalidate exactly one.
fixture_variables() {
  unset VORTEX_NOTIFY_ACQUIA_SKIP
  unset VORTEX_NOTIFY_ENVIRONMENT_DOMAIN
  export AH_SITE_NAME="test-site"
}

@test "hook-notify-deployment: missing site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: empty site argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh "" dev main abc123
  assert_failure
  assert_output_contains "Missing required site name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: missing target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: empty target environment argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars "" main abc123
  assert_failure
  assert_output_contains "Missing required target environment name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: missing branch argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars dev
  assert_failure
  assert_output_contains "Missing required branch name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: empty branch argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars dev "" abc123
  assert_failure
  assert_output_contains "Missing required branch name."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: missing commit reference argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars dev main
  assert_failure
  assert_output_contains "Missing required commit reference."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: empty commit reference argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables

  run ./hooks/library/notify-deployment.sh star_wars dev main ""
  assert_failure
  assert_output_contains "Missing required commit reference."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_NOTIFY_ACQUIA_SKIP=1

  run ./hooks/library/notify-deployment.sh star_wars dev main abc123
  assert_success
  assert_output_contains "Skipping sending of deployment notifications in Acquia environment."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: skip flag other than 1" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export VORTEX_NOTIFY_ACQUIA_SKIP=0

  run ./hooks/library/notify-deployment.sh star_wars dev main abc123
  assert_failure
  assert_output_not_contains "Skipping sending of deployment notifications in Acquia environment."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: unset site name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset AH_SITE_NAME

  run ./hooks/library/notify-deployment.sh star_wars dev main abc123
  assert_failure
  assert_output_contains "AH_SITE_NAME: Missing required value."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: empty site name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  export AH_SITE_NAME=""

  run ./hooks/library/notify-deployment.sh star_wars dev main abc123
  assert_failure
  assert_output_contains "AH_SITE_NAME: Missing required value."

  popd >/dev/null || exit 1
}

@test "hook-notify-deployment: domain override replaces the site name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_variables
  unset AH_SITE_NAME
  export VORTEX_NOTIFY_ENVIRONMENT_DOMAIN="www.example.com"

  run ./hooks/library/notify-deployment.sh star_wars dev main abc123
  assert_failure
  assert_output_not_contains "AH_SITE_NAME: Missing required value."

  popd >/dev/null || exit 1
}
