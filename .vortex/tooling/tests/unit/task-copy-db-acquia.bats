#!/usr/bin/env bats
##
# Unit tests for the 'vortex-task-copy-db-acquia' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the variables every scenario needs.
setup_variables() {
  export VORTEX_TASK_COPY_DB_ACQUIA_KEY="test-key"
  export VORTEX_TASK_COPY_DB_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_COPY_DB_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_COPY_DB_ACQUIA_SRC="dev"
  export VORTEX_TASK_COPY_DB_ACQUIA_DST="test"
  export VORTEX_TASK_COPY_DB_ACQUIA_NAME="testdb"
  export VORTEX_TASK_COPY_DB_ACQUIA_STATUS_RETRIES="1"
  export VORTEX_TASK_COPY_DB_ACQUIA_STATUS_INTERVAL="0"
}

@test "task-copy-db-acquia: copies the database between environments" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started database copying between environments in Acquia."

    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    "[ OK ] Retrieved authentication token."

    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    "[ OK ] Retrieved testapp application UUID."

    "Retrieving source environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev # {"_embedded":{"items":[{"id":"src-env-id"}]}}'
    "[ OK ] Retrieved source environment ID."

    "Retrieving destination environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest # {"_embedded":{"items":[{"id":"dst-env-id"}]}}'
    "[ OK ] Retrieved destination environment ID."

    "Copying database from dev to test environment."
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"source":"src-env-id", "name":"testdb"} https://cloud.acquia.com/api/environments/dst-env-id/databases # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notif-123"}}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"completed"}'
    "[ OK ] Copied database from dev to test environment."

    "[ OK ] Finished database copying between environments in Acquia."
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: polls with the refreshed token after the first poll" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev # {"_embedded":{"items":[{"id":"src-env-id"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest # {"_embedded":{"items":[{"id":"dst-env-id"}]}}'
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"source":"src-env-id", "name":"testdb"} https://cloud.acquia.com/api/environments/dst-env-id/databases # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notif-123"}}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"in-progress"}'
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"refreshed-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer refreshed-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"completed"}'

    "Refreshed authentication token."
    "[ OK ] Copied database from dev to test environment."
    "[ OK ] Finished database copying between environments in Acquia."
  )

  setup_variables
  export VORTEX_TASK_COPY_DB_ACQUIA_STATUS_RETRIES="2"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: fails when the task never completes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev # {"_embedded":{"items":[{"id":"src-env-id"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest # {"_embedded":{"items":[{"id":"dst-env-id"}]}}'
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"source":"src-env-id", "name":"testdb"} https://cloud.acquia.com/api/environments/dst-env-id/databases # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notif-123"}}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"in-progress"}'
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"refreshed-token"}'

    "[FAIL] Unable to copy database from dev to test environment."
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: reports the rejected credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"error":"invalid_client"}'
    '[FAIL] Authentication failed. Check VORTEX_TASK_COPY_DB_ACQUIA_KEY or VORTEX_ACQUIA_KEY and VORTEX_TASK_COPY_DB_ACQUIA_SECRET or VORTEX_ACQUIA_SECRET. API response: {"error":"invalid_client"}'
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: reports the missing application" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[]}}'
    '[FAIL] Application "testapp" not found. Check application name and access permissions.'
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: reports the missing source environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev # {"_embedded":{"items":[]}}'
    '[FAIL] Environment "dev" not found in application "testapp". Check environment name.'
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: reports the missing destination environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev # {"_embedded":{"items":[{"id":"src-env-id"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest # {"_embedded":{"items":[]}}'
    '[FAIL] Environment "test" not found in application "testapp". Check environment name.'
  )

  setup_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-copy-db-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-copy-db-acquia: missing key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")

  setup_variables
  unset VORTEX_TASK_COPY_DB_ACQUIA_KEY
  unset VORTEX_ACQUIA_KEY

  run .vortex/tooling/src/vortex-task-copy-db-acquia
  assert_failure

  assert_output_contains "Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_KEY or VORTEX_ACQUIA_KEY."

  # Assert the guard stops the run before the token request. Without this, a key
  # reaching the script from any source turns the test into a live,
  # authenticated request to Acquia instead of a failed assertion.
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null || exit 1
}
