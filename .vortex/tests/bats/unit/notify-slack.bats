#!/usr/bin/env bats
##
# Unit tests for Slack notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: slack, branch" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Project: testproject"
  assert_output_contains 'Reference: "develop" branch'
  assert_output_contains "Environment: https://develop.testproject.com"
  assert_output_contains "Finished Slack notification."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: slack, PR" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="feature-123"
  export VORTEX_NOTIFY_PR_NUMBER="123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-123.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Project: testproject"
  assert_output_contains 'Reference: "PR-123"'
  assert_output_contains "Environment: https://pr-123.testproject.com"
  assert_output_contains "Finished Slack notification."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: slack, pre_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"

  # Pre-deployment forces github channel, but we're testing slack directly
  export VORTEX_NOTIFY_CHANNELS="github"

  # Test Slack script directly for pre-deployment
  export VORTEX_NOTIFY_CHANNELS="slack"
  run ./scripts/vortex/notify-slack.sh
  assert_success

  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  popd >/dev/null || exit 1
}

@test "Notify: slack, missing webhook" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  # No VORTEX_NOTIFY_SLACK_WEBHOOK set

  run ./scripts/vortex/notify.sh
  assert_failure

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Missing required value for VORTEX_NOTIFY_SLACK_WEBHOOK"
  assert_output_not_contains "Started Slack notification."
  assert_output_not_contains "Finished Slack notification."

  popd >/dev/null || exit 1
}

@test "Notify: slack, failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "400" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/INVALID"

  run ./scripts/vortex/notify.sh
  assert_failure

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Slack notification."
  assert_output_contains "Unable to send notification to Slack. HTTP status: 400"
  assert_output_not_contains "Finished Slack notification."

  popd >/dev/null || exit 1
}

@test "Notify: slack, custom channel and username" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"
  export VORTEX_NOTIFY_SLACK_CHANNEL="#custom-deployments"
  export VORTEX_NOTIFY_SLACK_USERNAME="Custom Deploy Bot"
  export VORTEX_NOTIFY_SLACK_ICON_EMOJI=":ship:"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: slack, with commit SHA" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_REF="develop"
  export VORTEX_NOTIFY_SHA="abc123def456789012345678901234567890abcd"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
