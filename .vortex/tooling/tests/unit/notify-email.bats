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
  run ./.vortex/tooling/src/vortex-notify
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
  run ./.vortex/tooling/src/vortex-notify
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
  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started email notification."
  assert_output_contains "Skipped email notification for pre_deployment event."
  assert_output_not_contains "Notification email(s) sent"
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email, branch filter skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="feature/test"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="feature/test"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_EMAIL_BRANCHES="main,develop"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Skipped email notification for branch 'feature/test'."
  assert_output_not_contains "Started email notification."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email, branch filter with unset branch skips gracefully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Direct invocation with branch filtering on but VORTEX_NOTIFY_BRANCH unset
  # must not trip 'set -u'. The empty branch is not in the allowlist, so the
  # notification is skipped gracefully instead of aborting.
  unset VORTEX_NOTIFY_BRANCH
  export VORTEX_NOTIFY_EMAIL_BRANCHES="main,develop"

  run ./.vortex/tooling/src/vortex-notify-email
  assert_success

  assert_output_contains "Skipped email notification for branch ''."
  assert_output_not_contains "unbound variable"

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

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  # Verify the injection file was NOT created (injection did not execute)
  [ ! -f /tmp/injected_email_test ]

  # Verify the malicious string is treated as literal text in the message
  assert_output_contains "test'); file_put_contents('/tmp/injected_email_test', 'HACKED'); //"

  popd >/dev/null || exit 1
}

@test "Notify: email, deployment log included in body" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  printf 'Provision line one\nProvision line two\n' >"${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  # Each collected log is a titled section, below the login URL.
  assert_output_contains "## provision.log ##"
  assert_output_contains "Provision line one"
  assert_output_contains "Provision line two"

  popd >/dev/null || exit 1
}

@test "Notify: email, missing deployment log directory leaves body intact" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/nologs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_contains "Site testproject develop has been deployed"
  assert_output_not_contains "## provision.log ##"

  popd >/dev/null || exit 1
}

@test "Notify: email, empty deployment log leaves body intact" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  touch "${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_contains "Site testproject develop has been deployed"
  assert_output_not_contains "## provision.log ##"

  popd >/dev/null || exit 1
}

@test "Notify: email, deployment log content is treated as literal text" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -f "${BATS_TEST_TMPDIR}/pwned"
  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  # The literal command substitution below is intentional test data.
  # shellcheck disable=SC2016
  printf '%%project%% literal $(touch "%s/pwned")\n' "${BATS_TEST_TMPDIR}" >"${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  # The %project% token inside the log is inserted last, so it stays literal.
  assert_output_contains "%project% literal"
  # The command substitution embedded in the log never executes.
  [ ! -f "${BATS_TEST_TMPDIR}/pwned" ]

  popd >/dev/null || exit 1
}

@test "Notify: email, deployment log excluded when flag disabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  printf 'Provision line one\nProvision line two\n' >"${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  # Logs exist in the directory, but the feature flag is left disabled.
  unset VORTEX_NOTIFY_LOG
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_not_contains "Provision line one"

  popd >/dev/null || exit 1
}

@test "Notify: email, deployment log token in custom template" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  printf 'CUSTOM log line one\nCUSTOM log line two\n' >"${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_EMAIL_MESSAGE="Custom body. Log below: %deployment_log%"
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  # A custom template's %deployment_log% token is substituted with the log.
  assert_output_contains "Custom body. Log below:"
  assert_output_contains "CUSTOM log line one"
  assert_output_contains "CUSTOM log line two"

  popd >/dev/null || exit 1
}

@test "Notify: email, per-channel flag overrides the common flag" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "${BATS_TEST_TMPDIR}/logs"
  printf 'Provision line one\n' >"${BATS_TEST_TMPDIR}/logs/provision.log"

  export VORTEX_NOTIFY_CHANNELS="email"
  export VORTEX_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export VORTEX_NOTIFY_EMAIL_RECIPIENTS="john@example.com"
  export VORTEX_NOTIFY_BRANCH="develop"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="develop"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_LOG_DIR="${BATS_TEST_TMPDIR}/logs"
  # Enabled globally, but disabled for the email channel specifically.
  export VORTEX_NOTIFY_LOG=1
  export VORTEX_NOTIFY_EMAIL_LOG=0

  run ./.vortex/tooling/src/vortex-notify
  assert_success

  assert_output_not_contains "Provision line one"

  popd >/dev/null || exit 1
}
