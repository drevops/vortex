#!/usr/bin/env bats
##
# Unit tests for the reset script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "reset: soft reset skips the hard-reset steps" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/reset
  assert_success
  assert_output_contains "Started reset."
  assert_output_contains "Finished reset."
  assert_output_not_contains "Changing permissions and removing all other untracked files."
  assert_output_not_contains "Resetting repository files."
  assert_output_not_contains "Removing all untracked files."
  assert_output_not_contains "Removing empty directories."

  popd >/dev/null
}

@test "reset: --hard runs the hard-reset steps" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Stub git so the hard-reset branch runs without mutating the fixture repo.
  mock_command "git" >/dev/null

  run .vortex/tooling/src/reset --hard
  assert_success
  assert_output_contains "Started reset."
  assert_output_contains "Changing permissions and removing all other untracked files."
  assert_output_contains "Resetting repository files."
  assert_output_contains "Removing all untracked files."
  assert_output_contains "Removing empty directories."
  assert_output_contains "Finished reset."

  popd >/dev/null
}

@test "reset: legacy 'hard' positional no longer triggers a hard reset" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/reset hard
  assert_success
  assert_output_contains "Started reset."
  assert_output_contains "Finished reset."
  assert_output_not_contains "Resetting repository files."

  popd >/dev/null
}
