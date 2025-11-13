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

  mock_set_output "${mock_curl}" "12345678910-1234567890-${app_id}-12345" 1
  mock_set_output "${mock_curl}" "201" 2

  export VORTEX_NOTIFY_CHANNELS="newrelic"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_NEWRELIC_APIKEY="key1234"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe,jane@example.com|Jane Doe"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="123456"

  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started New Relic notification."
  assert_output_contains "Discovering APP id by name if it was not provided."
  assert_output_contains "Checking if the application ID is valid."
  assert_output_contains "Creating a deployment notification for application testproject-develop with ID 9876543210."

  assert_equal "-s -X GET https://api.newrelic.com/v2/applications.json -H Api-Key:key1234 -s -G -d filter[name]=testproject-develop&exclude_links=true" "$(mock_get_call_args "${mock_curl}" 1)"
  assert_equal '-X POST https://api.newrelic.com/v2/applications/9876543210/deployments.json -L -s -o /dev/null -w %{http_code} -H Api-Key:key1234 -H Content-Type: application/json -d {
  "deployment": {
    "revision": "123456",
    "changelog": "develop deployed",
    "description": "develop deployed",
    "user": "Deployment robot"
  }
}' "$(mock_get_call_args "${mock_curl}" 2)"

  assert_output_contains "Finished New Relic notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: newrelic, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="newrelic"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export VORTEX_NOTIFY_NEWRELIC_APIKEY="key1234"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="123456"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started New Relic notification."
  assert_output_contains "Skipping New Relic notification for pre_deployment event."
  assert_output_not_contains "Discovering APP id"
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
