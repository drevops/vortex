#!/usr/bin/env bats
##
# Unit tests for NewRelic notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: newrelic" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="9876543210"
  mock_curl=$(mock_command "curl")

  mock_set_output "${mock_curl}" "{\"applications\": [{\"id\": ${app_id}, \"name\": \"testproject-develop\"}]}" 1
  mock_set_output "${mock_curl}" "201" 2

  export VORTEX_NOTIFY_CHANNELS="newrelic"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_NEWRELIC_USER_KEY="key1234"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe,jane@example.com|Jane Doe"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://test.example.com"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started New Relic notification."
  assert_output_contains "Discovering APP id by name if it was not provided."
  assert_output_contains "Checking if the application ID is valid."
  assert_output_contains "Creating a deployment notification for application testproject-develop with ID 9876543210."

  assert_equal "-s -X GET https://api.newrelic.com/v2/applications.json -H X-Api-Key:key1234 -s -G -d filter[name]=testproject-develop&exclude_links=true" "$(mock_get_call_args "${mock_curl}" 1)"

  # Extract revision from actual curl call (since it's auto-generated with timestamp)
  actual_curl_call="$(mock_get_call_args "${mock_curl}" 2)"
  assert_output_contains "Creating a deployment notification"

  # Verify the call structure without checking exact revision value
  [[ ${actual_curl_call} =~ -X[[:space:]]POST[[:space:]]https://api.newrelic.com/v2/applications/9876543210/deployments.json ]]
  [[ ${actual_curl_call} =~ -H[[:space:]]X-Api-Key:key1234 ]]
  [[ ${actual_curl_call} =~ \"revision\": ]]
  [[ ${actual_curl_call} =~ \"user\":[[:space:]]\"Deployment[[:space:]]robot\" ]]

  assert_output_contains "Finished New Relic notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: newrelic, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="newrelic"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_NEWRELIC_USER_KEY="key1234"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://test.example.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started New Relic notification."
  assert_output_contains "Skipping New Relic notification for pre_deployment event."
  assert_output_not_contains "Discovering APP id"
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
