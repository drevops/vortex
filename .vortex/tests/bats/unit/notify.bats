#!/usr/bin/env bats
##
# Unit tests for general notify.sh functionality.
#
# Notification-specific tests are in separate files:
# - notify-email.bats
# - notify-newrelic.bats
# - notify-github.bats
# - notify-jira.bats
# - notify-webhook.bats
# - notify-slack.bats
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_SKIP=1
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Skipping dispatching notifications."
  assert_output_not_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: unsupported event" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_EVENT="customevent"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://test.example.com"
  run ./scripts/vortex/notify.sh
  assert_failure

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Unsupported event customevent provided."
  assert_output_not_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: pre_deployment event allows all channels" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock curl for channels that actually send notifications during pre_deployment
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1
  mock_set_output "${mock_curl}" '{"id": "123456789", "state": "success"}' 2
  mock_set_output "${mock_curl}" "200" 3

  export VORTEX_NOTIFY_CHANNELS="email,slack,github,newrelic,webhook,jira"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://test.example.com"

  # Email required variables
  export DRUPAL_SITE_EMAIL="test@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="test@example.com"

  # Slack required variables
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/test"

  # GitHub required variables
  export VORTEX_NOTIFY_GITHUB_TOKEN="test_token"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="owner/repo"

  # NewRelic required variables
  export VORTEX_NOTIFY_NEWRELIC_USER_KEY="test_key"

  # Webhook required variables
  export VORTEX_NOTIFY_WEBHOOK_URL="https://webhook.example.com"

  # JIRA required variables
  export VORTEX_NOTIFY_JIRA_USER_EMAIL="test@example.com"
  export VORTEX_NOTIFY_JIRA_TOKEN="test_token"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  # Email: should skip pre_deployment
  assert_output_contains "Started email notification."
  assert_output_contains "Skipping email notification for pre_deployment event."

  # Slack: should execute for pre_deployment
  assert_output_contains "Started Slack notification."
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  # NewRelic: should skip pre_deployment
  assert_output_contains "Started New Relic notification."
  assert_output_contains "Skipping New Relic notification for pre_deployment event."

  # GitHub: should execute for pre_deployment
  assert_output_contains "Started GitHub notification for pre_deployment event."
  assert_output_contains "Marked deployment as started."
  assert_output_contains "Finished GitHub notification for pre_deployment event."

  # Webhook: should skip pre_deployment
  assert_output_contains "Started Webhook notification."
  assert_output_contains "Skipping Webhook notification for pre_deployment event."

  # JIRA: should skip pre_deployment
  assert_output_contains "Started JIRA notification."
  assert_output_contains "Skipping JIRA notification for pre_deployment event."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: custom type" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="customtype"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://test.example.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
