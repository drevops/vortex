#!/usr/bin/env bats
##
# Unit tests for Diffy notifications (notify-diffy).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: diffy, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="diffy"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-123.example.com"
  export VORTEX_NOTIFY_DIFFY_TOKEN="token12345"
  export VORTEX_NOTIFY_DIFFY_REPOSITORY="myorg/myrepo"

  run ./.vortex/tooling/src/notify
  assert_success

  assert_output_contains "Started Diffy notification."
  assert_output_contains "Skipping Diffy notification for pre_deployment event."

  popd >/dev/null || exit 1
}

@test "Notify: diffy, post_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "204" 1

  export VORTEX_NOTIFY_CHANNELS="diffy"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-123.example.com"
  export VORTEX_NOTIFY_DIFFY_TOKEN="token12345"
  export VORTEX_NOTIFY_DIFFY_REPOSITORY="myorg/myrepo"

  run ./.vortex/tooling/src/notify
  assert_success

  assert_output_contains "Started Diffy notification."
  assert_output_contains "Branch           : feature/my-pr-branch"
  assert_output_contains "Environment URL  : https://pr-123.example.com"
  assert_output_contains "Finished Diffy notification."

  popd >/dev/null || exit 1
}

@test "Notify: diffy, branch filter skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="diffy"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_BRANCH="feature/random"
  export VORTEX_NOTIFY_LABEL="feature/random"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://random.example.com"
  export VORTEX_NOTIFY_DIFFY_TOKEN="token12345"
  export VORTEX_NOTIFY_DIFFY_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_DIFFY_BRANCHES="develop,main"

  run ./.vortex/tooling/src/notify
  assert_success

  assert_output_contains "Skipping Diffy notification for branch 'feature/random'"

  popd >/dev/null || exit 1
}

@test "Notify: diffy, dispatch failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "401" 1

  export VORTEX_NOTIFY_CHANNELS="diffy"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-123.example.com"
  export VORTEX_NOTIFY_DIFFY_TOKEN="badtoken"
  export VORTEX_NOTIFY_DIFFY_REPOSITORY="myorg/myrepo"

  run ./.vortex/tooling/src/notify
  assert_failure

  assert_output_contains "GitHub repository_dispatch failed with HTTP 401"

  popd >/dev/null || exit 1
}

@test "Notify: diffy, missing token" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="diffy"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-123.example.com"
  export VORTEX_NOTIFY_DIFFY_TOKEN=""
  export VORTEX_NOTIFY_DIFFY_REPOSITORY="myorg/myrepo"

  run ./.vortex/tooling/src/notify
  assert_failure

  assert_output_contains "Missing required value for VORTEX_NOTIFY_DIFFY_TOKEN"

  popd >/dev/null || exit 1
}
