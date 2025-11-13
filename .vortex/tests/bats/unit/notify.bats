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
  run ./scripts/vortex/notify.sh
  assert_failure

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Unsupported event customevent provided."
  assert_output_not_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: custom type" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="customtype"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
