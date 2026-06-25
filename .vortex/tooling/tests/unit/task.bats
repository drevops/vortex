#!/usr/bin/env bats
##
# Unit tests for the task router.
#
# The task-specific logic lives in the dispatched sibling scripts
# (task-copy-db-acquia, task-copy-files-acquia, task-purge-cache-acquia); these
# tests cover only the router's command resolution and error handling.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Task: missing command" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task
  assert_failure
  assert_output_contains "Missing task command."

  popd >/dev/null || exit 1
}

@test "Task: unsupported command" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task invalid-command
  assert_failure
  assert_output_contains "Unsupported task command 'invalid-command' provided."

  popd >/dev/null || exit 1
}

@test "Task: dispatches copy-db-acquia" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Without credentials the sibling prints its banner and then fails on the
  # missing-key guard before any network call, which proves the routing.
  run ./.vortex/tooling/src/task copy-db-acquia
  assert_failure
  assert_output_contains "Started database copying between environments in Acquia."

  popd >/dev/null || exit 1
}

@test "Task: dispatches copy-files-acquia" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task copy-files-acquia
  assert_failure
  assert_output_contains "Started files copying between environments in Acquia."

  popd >/dev/null || exit 1
}

@test "Task: dispatches purge-cache-acquia" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task purge-cache-acquia
  assert_failure
  assert_output_contains "Started cache purging in Acquia."

  popd >/dev/null || exit 1
}
