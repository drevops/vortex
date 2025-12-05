#!/usr/bin/env bats
##
# Unit tests for email notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: email, branch" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe, jane@example.com|Jane Doe, jim@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started email notification."
  assert_output_contains "Notification email(s) sent to: john@example.com, jane@example.com, jim@example.com"
  assert_output_contains "Finished email notification."

  assert_output_contains 'testproject deployment notification of develop'
  assert_output_contains 'Site testproject develop has been deployed'
  assert_output_contains "and is available at https://develop.testproject.com."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email, PR" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe, jane@example.com|Jane Doe"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_PR_NUMBER="123"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started email notification."
  assert_output_contains "Notification email(s) sent to: john@example.com, jane@example.com"
  assert_output_contains "Finished email notification."

  assert_output_contains 'testproject deployment notification of PR-123'
  assert_output_contains 'Site testproject PR-123 has been deployed'
  assert_output_contains "and is available at https://develop.testproject.com."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started email notification."
  assert_output_contains "Skipping email notification for pre_deployment event."
  assert_output_not_contains "Notification email(s) sent"
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email, shell injection protection" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Attempt shell injection through project name with PHP code that would create a file
  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="test'); file_put_contents('/tmp/injected_email_test', 'HACKED'); //"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"

  # Ensure test file doesn't exist before
  rm -f /tmp/injected_email_test

  run ./scripts/vortex/notify.sh
  assert_success

  # Verify the injection file was NOT created (injection did not execute)
  [ ! -f /tmp/injected_email_test ]

  # Verify the malicious string is treated as literal text in the message
  assert_output_contains "test'); file_put_contents('/tmp/injected_email_test', 'HACKED'); //"

  popd >/dev/null || exit 1
}
