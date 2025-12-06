#!/usr/bin/env bats
##
# Unit tests for Slack notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: slack, branch pre_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_SLACK_PROJECT="testproject"
  export VORTEX_NOTIFY_SLACK_LABEL="develop"
  export VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_LOGIN_URL="https://develop.testproject.com/user/login"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"
  export VORTEX_NOTIFY_SLACK_CHANNEL="#deployments"
  export VORTEX_NOTIFY_SLACK_USERNAME="Deploy Bot"
  export VORTEX_NOTIFY_SLACK_ICON_EMOJI=":rocket:"
  export VORTEX_NOTIFY_SLACK_EVENT="pre_deployment"

  run ./scripts/vortex/notify-slack.sh
  assert_success

  # Assert script output
  assert_output_contains "Started Slack notification."
  assert_output_contains "Project        : testproject"
  assert_output_contains "Deployment     : develop"
  assert_output_contains "Environment URL: https://develop.testproject.com"
  assert_output_contains "Login URL      : https://develop.testproject.com/user/login"
  assert_output_contains "Channel        : #deployments"
  assert_output_contains "Username       : Deploy Bot"
  assert_output_contains "Event          : Deployment Starting"
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  # Verify curl payload does NOT contain View Site or Login Here for pre-deployment
  run mock_get_call_args "${mock_curl}" 1
  assert_output_contains "Deployment"
  assert_output_contains "Time"
  assert_output_not_contains "View Site"
  assert_output_not_contains "Login Here"

  popd >/dev/null || exit 1
}

@test "Notify: slack, branch post_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_SLACK_PROJECT="testproject"
  export VORTEX_NOTIFY_SLACK_LABEL="develop"
  export VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_SLACK_LOGIN_URL="https://develop.testproject.com/user/login"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"
  export VORTEX_NOTIFY_SLACK_CHANNEL="#deployments"
  export VORTEX_NOTIFY_SLACK_USERNAME="Deploy Bot"
  export VORTEX_NOTIFY_SLACK_ICON_EMOJI=":rocket:"
  export VORTEX_NOTIFY_SLACK_EVENT="post_deployment"

  run ./scripts/vortex/notify-slack.sh
  assert_success

  # Assert script output
  assert_output_contains "Started Slack notification."
  assert_output_contains "Project        : testproject"
  assert_output_contains "Deployment     : develop"
  assert_output_contains "Environment URL: https://develop.testproject.com"
  assert_output_contains "Login URL      : https://develop.testproject.com/user/login"
  assert_output_contains "Channel        : #deployments"
  assert_output_contains "Username       : Deploy Bot"
  assert_output_contains "Event          : Deployment Complete"
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  # Verify curl payload DOES contain View Site and Login Here for post-deployment
  run mock_get_call_args "${mock_curl}" 1
  assert_output_contains "Deployment"
  assert_output_contains "Time"
  assert_output_contains "View Site"
  assert_output_contains "Login Here"

  popd >/dev/null || exit 1
}

@test "Notify: slack, PR pre_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_SLACK_PROJECT="testproject"
  export VORTEX_NOTIFY_SLACK_LABEL="PR-123"
  export VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL="https://pr-123.testproject.com"
  export VORTEX_NOTIFY_SLACK_LOGIN_URL="https://pr-123.testproject.com/user/login"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"
  export VORTEX_NOTIFY_SLACK_CHANNEL="#deployments"
  export VORTEX_NOTIFY_SLACK_USERNAME="Deploy Bot"
  export VORTEX_NOTIFY_SLACK_ICON_EMOJI=":rocket:"
  export VORTEX_NOTIFY_SLACK_EVENT="pre_deployment"

  run ./scripts/vortex/notify-slack.sh
  assert_success

  # Assert script output
  assert_output_contains "Started Slack notification."
  assert_output_contains "Project        : testproject"
  assert_output_contains "Deployment     : PR-123"
  assert_output_contains "Environment URL: https://pr-123.testproject.com"
  assert_output_contains "Login URL      : https://pr-123.testproject.com/user/login"
  assert_output_contains "Channel        : #deployments"
  assert_output_contains "Username       : Deploy Bot"
  assert_output_contains "Event          : Deployment Starting"
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  # Verify curl payload does NOT contain View Site or Login Here for pre-deployment
  run mock_get_call_args "${mock_curl}" 1
  assert_output_contains "Deployment"
  assert_output_contains "Time"
  assert_output_not_contains "View Site"
  assert_output_not_contains "Login Here"

  popd >/dev/null || exit 1
}

@test "Notify: slack, PR post_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_SLACK_PROJECT="testproject"
  export VORTEX_NOTIFY_SLACK_LABEL="PR-123"
  export VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL="https://pr-123.testproject.com"
  export VORTEX_NOTIFY_SLACK_LOGIN_URL="https://pr-123.testproject.com/user/login"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX"
  export VORTEX_NOTIFY_SLACK_CHANNEL="#deployments"
  export VORTEX_NOTIFY_SLACK_USERNAME="Deploy Bot"
  export VORTEX_NOTIFY_SLACK_ICON_EMOJI=":rocket:"
  export VORTEX_NOTIFY_SLACK_EVENT="post_deployment"

  run ./scripts/vortex/notify-slack.sh
  assert_success

  # Assert script output
  assert_output_contains "Started Slack notification."
  assert_output_contains "Project        : testproject"
  assert_output_contains "Deployment     : PR-123"
  assert_output_contains "Environment URL: https://pr-123.testproject.com"
  assert_output_contains "Login URL      : https://pr-123.testproject.com/user/login"
  assert_output_contains "Channel        : #deployments"
  assert_output_contains "Username       : Deploy Bot"
  assert_output_contains "Event          : Deployment Complete"
  assert_output_contains "Notification sent to Slack."
  assert_output_contains "Finished Slack notification."

  # Verify curl payload DOES contain View Site and Login Here for post-deployment
  run mock_get_call_args "${mock_curl}" 1
  assert_output_contains "Deployment"
  assert_output_contains "Time"
  assert_output_contains "View Site"
  assert_output_contains "Login Here"

  popd >/dev/null || exit 1
}

@test "Notify: slack, missing webhook" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
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
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
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
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
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
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
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

@test "Notify: slack, shell injection protection" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  # Attempt shell injection through project name with PHP code that would create a file
  export VORTEX_NOTIFY_CHANNELS="slack"
  export VORTEX_NOTIFY_SLACK_WEBHOOK="https://hooks.slack.com/services/TEST/HOOK"
  export VORTEX_NOTIFY_PROJECT="test'); file_put_contents('/tmp/injected_slack_test', 'HACKED'); //"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"

  # Ensure test file doesn't exist before
  rm -f /tmp/injected_slack_test

  run ./scripts/vortex/notify.sh
  assert_success

  # Verify the injection file was NOT created (injection did not execute)
  [ ! -f /tmp/injected_slack_test ]

  # Verify the malicious string is treated as literal text in the fallback message
  assert_output_contains "test'); file_put_contents('/tmp/injected_slack_test', 'HACKED'); //"

  popd >/dev/null || exit 1
}
