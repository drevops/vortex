#!/usr/bin/env bats
##
# Unit tests for the task router.
#
# The router resolves a platform-agnostic operation to the
# 'task-<operation>-<platform>' sibling that implements it; these tests cover
# operation validation, platform resolution, and dispatch. The
# operation-specific logic is owned by the sibling scripts.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Task: missing operation" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task
  assert_failure
  assert_output_contains "Missing task operation."

  popd >/dev/null || exit 1
}

@test "Task: unsupported operation" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run ./.vortex/tooling/src/task invalid-operation
  assert_failure
  assert_output_contains "Unsupported task operation 'invalid-operation'."

  popd >/dev/null || exit 1
}

@test "Task: missing platform" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_TASK_PLATFORM
  unset VORTEX_PLATFORM
  run ./.vortex/tooling/src/task copy-db
  assert_failure
  assert_output_contains "Missing hosting platform. Set VORTEX_PLATFORM or VORTEX_TASK_PLATFORM."

  popd >/dev/null || exit 1
}

@test "Task: unsupported platform" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_TASK_PLATFORM
  export VORTEX_PLATFORM=invalid-platform
  run ./.vortex/tooling/src/task copy-db
  assert_failure
  assert_output_contains "Unsupported hosting platform 'invalid-platform'."

  popd >/dev/null || exit 1
}

@test "Task: operation not supported on platform" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Lagoon has no copy-db implementation, so the sibling resolution fails.
  unset VORTEX_TASK_PLATFORM
  export VORTEX_PLATFORM=lagoon
  run ./.vortex/tooling/src/task copy-db
  assert_failure
  assert_output_contains "Operation 'copy-db' is not supported on the 'lagoon' platform."

  popd >/dev/null || exit 1
}

@test "Task: platform from VORTEX_PLATFORM dispatches copy-db" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Without credentials the sibling prints its banner and then fails on the
  # missing-key guard before any network call, which proves the routing.
  unset VORTEX_TASK_PLATFORM
  export VORTEX_PLATFORM=acquia
  run ./.vortex/tooling/src/task copy-db
  assert_failure
  assert_output_contains "Started database copying between environments in Acquia."

  popd >/dev/null || exit 1
}

@test "Task: VORTEX_TASK_PLATFORM overrides VORTEX_PLATFORM" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_PLATFORM=lagoon
  export VORTEX_TASK_PLATFORM=acquia
  run ./.vortex/tooling/src/task copy-db
  assert_failure
  assert_output_contains "Started database copying between environments in Acquia."

  popd >/dev/null || exit 1
}

@test "Task: platform override dispatches copy-files" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_TASK_PLATFORM=acquia
  run ./.vortex/tooling/src/task copy-files
  assert_failure
  assert_output_contains "Started files copying between environments in Acquia."

  popd >/dev/null || exit 1
}

@test "Task: platform from VORTEX_PLATFORM dispatches purge-cache" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_TASK_PLATFORM
  export VORTEX_PLATFORM=acquia
  run ./.vortex/tooling/src/task purge-cache
  assert_failure
  assert_output_contains "Started cache purging in Acquia."

  popd >/dev/null || exit 1
}
