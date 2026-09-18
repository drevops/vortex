#!/usr/bin/env bats
##
# Unit tests for the 'vortex-check-worktree-changes' script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "check-worktree-changes: clean working tree passes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_success
  assert_output_contains "Started working tree check."
  assert_output_contains "Finished working tree check."

  popd >/dev/null
}

@test "check-worktree-changes: modified tracked file fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "Modified by the build." >>composer.json

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_failure
  assert_output_contains "The build modified or created these files:"
  assert_output_contains "M composer.json"
  assert_output_contains 'Run "ahoy build" locally and commit the resulting changes.'
  assert_output_contains "Add paths that are generated during the build and are not committed to VORTEX_CHECK_WORKTREE_CHANGES_IGNORE."
  assert_output_contains "The build left changes in the working tree."

  popd >/dev/null
}

@test "check-worktree-changes: untracked file fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  touch build-manifest.json

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_failure
  assert_output_contains "?? build-manifest.json"
  assert_output_contains "The build left changes in the working tree."

  popd >/dev/null
}

@test "check-worktree-changes: file ignored by Git passes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .logs
  touch .logs/build.log

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_success
  assert_output_contains "Finished working tree check."

  popd >/dev/null
}

@test "check-worktree-changes: shipped ignore list covers the paths written by the pipeline" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "# Processed to run in CI." >>docker-compose.yml
  touch web/sites/default/docker-compose.yml
  touch db_cache_branch db_cache_timestamp
  touch codecov codecov.SHA256SUM codecov.SHA256SUM.sig

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_success
  assert_output_contains 'Ignoring "docker-compose.yml".'
  assert_output_contains 'Ignoring "*/docker-compose.yml".'
  assert_output_contains 'Ignoring "db_cache_*".'
  assert_output_contains 'Ignoring "codecov".'
  assert_output_contains 'Ignoring "codecov.SHA256SUM".'
  assert_output_contains 'Ignoring "codecov.SHA256SUM.sig".'
  assert_output_contains "Finished working tree check."

  popd >/dev/null
}

@test "check-worktree-changes: shipped ignore list does not cover the Codecov config file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  touch codecov.yml

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_failure
  assert_output_contains "?? codecov.yml"

  popd >/dev/null
}

@test "check-worktree-changes: custom ignore list excludes a path" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CHECK_WORKTREE_CHANGES_IGNORE="build-manifest.json"
  touch build-manifest.json

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_success
  assert_output_contains 'Ignoring "build-manifest.json".'
  assert_output_contains "Finished working tree check."

  popd >/dev/null
}

@test "check-worktree-changes: custom ignore list replaces the shipped one" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CHECK_WORKTREE_CHANGES_IGNORE="build-manifest.json"
  echo "# Processed to run in CI." >>docker-compose.yml

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_failure
  assert_output_contains "M docker-compose.yml"

  popd >/dev/null
}

@test "check-worktree-changes: blank entries in the ignore list are skipped" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CHECK_WORKTREE_CHANGES_IGNORE=" , build-manifest.json , "
  touch build-manifest.json

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_success
  assert_output_contains 'Ignoring "build-manifest.json".'

  popd >/dev/null
}

@test "check-worktree-changes: ignore list of separators only checks every path" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CHECK_WORKTREE_CHANGES_IGNORE=","
  echo "# Processed to run in CI." >>docker-compose.yml

  run .vortex/tooling/src/vortex-check-worktree-changes
  assert_failure
  assert_output_not_contains "Ignoring "
  assert_output_contains "M docker-compose.yml"

  popd >/dev/null
}

@test "check-worktree-changes: fails outside a Git repository" {
  cp "${LOCAL_REPO_DIR}/.env" "${CURRENT_PROJECT_DIR}/.env"

  run "${LOCAL_REPO_DIR}/.vortex/tooling/src/vortex-check-worktree-changes"
  assert_failure
  assert_output_contains "The current directory is not a Git repository."
}
