#!/usr/bin/env bats
##
# Unit tests for webhook notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: webhook" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export VORTEX_NOTIFY_CHANNELS="webhook"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"

  export VORTEX_NOTIFY_WEBHOOK_URL="https://example-webhook-url.com"
  export VORTEX_NOTIFY_WEBHOOK_METHOD="POST"
  export VORTEX_NOTIFY_WEBHOOK_HEADERS="Content-type: application/json|Authorization: Bearer API_KEY"
  export VORTEX_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Test channel 1", "message": "Test channel 1 message"}'

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started Webhook notification."

  assert_output_contains "Finished Webhook notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: webhook, failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "400" 1

  export VORTEX_NOTIFY_CHANNELS="webhook"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"

  export VORTEX_NOTIFY_WEBHOOK_URL="https://example-webhook-url.com"
  export VORTEX_NOTIFY_WEBHOOK_METHOD="POST"
  export VORTEX_NOTIFY_WEBHOOK_HEADERS="Content-type: application/json|Authorization: Bearer API_KEY"
  export VORTEX_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Test channel 1", "message": "Test channel 1 message"}'

  run ./scripts/vortex/notify.sh
  assert_failure

  popd >/dev/null || exit 1
}

@test "Notify: webhook, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="webhook"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_WEBHOOK_URL="https://example-webhook-url.com"
  export VORTEX_NOTIFY_WEBHOOK_METHOD="POST"
  export VORTEX_NOTIFY_WEBHOOK_HEADERS="Content-type: application/json|Authorization: Bearer API_KEY"
  export VORTEX_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Test channel 1", "message": "Test channel 1 message"}'
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started Webhook notification."
  assert_output_contains "Skipping Webhook notification for pre_deployment event."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: webhook, shell injection protection" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  # Attempt shell injection through project name with PHP code that would create a file
  export VORTEX_NOTIFY_CHANNELS="webhook"
  export VORTEX_NOTIFY_WEBHOOK_URL="https://example.com/webhook"
  export VORTEX_NOTIFY_PROJECT="test'); file_put_contents('/tmp/injected_webhook_test', 'HACKED'); //"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"

  # Ensure test file doesn't exist before
  rm -f /tmp/injected_webhook_test

  run ./scripts/vortex/notify.sh
  assert_success

  # Verify the injection file was NOT created (injection did not execute)
  [ ! -f /tmp/injected_webhook_test ]

  # Verify the malicious string is treated as literal text in the payload
  assert_output_contains "test'); file_put_contents('/tmp/injected_webhook_test', 'HACKED'); //"

  popd >/dev/null || exit 1
}
