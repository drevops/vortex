#!/usr/bin/env bats
##
# Unit tests for the 'vortex-task-purge-cache-acquia' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Runs the task for one environment and asserts the domain it purges.
#
# The Acquia API replies without a notification link, which the task reports as
# a missing domain and skips, so the run stops before the status polling.
#
# Arguments:
#   1. env_name: Environment to purge the cache for.
#   2. domain: Domain the placeholder is expected to resolve to.
#   3. domains_file: File to read the domain list from. Optional, defaults to
#      the domain list shipped with the template.
assert_purge_domain() {
  local env_name="${1}"
  local domain="${2}"
  local domains_file="${3:-hooks/library/domains.txt}"

  declare -a STEPS=(
    "[INFO] Started cache purging in Acquia."

    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    "[ OK ] Retrieved authentication token."

    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    "[ OK ] Retrieved testapp application UUID."

    "Retrieving environment ID."
    "@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3D${env_name} # {\"_embedded\":{\"items\":[{\"id\":\"env-id-456\"}]}}"
    "[ OK ] Retrieved environment ID."

    "Compiling a list of domains."
    "[ OK ] Compiled a list of 1 domains."

    "Purging cache for ${env_name} environment domain ${domain}."
    "@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {\"domains\":[\"${domain}\"]} https://cloud.acquia.com/api/environments/env-id-456/domains/actions/clear-varnish # {}"
    "Warning: Unable to purge cache for ${env_name} environment domain ${domain} as it does not exist."
    "[ OK ] Completed cache purging for ${env_name} environment domain ${domain}."

    "[ OK ] Finished cache purging in Acquia."
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="${env_name}"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${domains_file}"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success
}

@test "task-purge-cache-acquia: prod strips the placeholder from the shipped domain list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_purge_domain "prod" "your-site-domain.example"

  # A placeholder that survives the strip is interpolated into the environment
  # name, which is the prefix production must not carry.
  assert_output_not_contains "prod.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: prod strips the legacy placeholder" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # shellcheck disable=SC2016
  echo '$TARGET_ENV.your-site-domain.example' >domains-legacy.txt

  assert_purge_domain "prod" "your-site-domain.example" "domains-legacy.txt"

  assert_output_not_contains "prod.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: dev prefixes the domain with the environment name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_purge_domain "dev" "dev.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: test prefixes the domain with the stage name used in the UI" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_purge_domain "test" "stage.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: test2 prefixes the domain with the environment name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_purge_domain "test2" "test2.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: unknown environment compiles no domains" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started cache purging in Acquia."

    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'

    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'

    "Retrieving environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dunknownenv # {"_embedded":{"items":[{"id":"env-id-456"}]}}'

    "[ OK ] Compiled a list of 0 domains."
    "Unable to find domains to purge cache for unknownenv environment."

    "[ OK ] Finished cache purging in Acquia."
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="unknownenv"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="hooks/library/domains.txt"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: prod purge completes for an existing domain" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started cache purging in Acquia."

    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'

    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'

    "Retrieving environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456"}]}}'

    "[ OK ] Compiled a list of 1 domains."

    "Purging cache for prod environment domain your-site-domain.example."
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"domains":["your-site-domain.example"]} https://cloud.acquia.com/api/environments/env-id-456/domains/actions/clear-varnish # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notif-123"}}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"completed"}'
    "Purged cache for prod environment domain your-site-domain.example."
    "[ OK ] Completed cache purging for prod environment domain your-site-domain.example."

    "[ OK ] Finished cache purging in Acquia."
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="prod"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="hooks/library/domains.txt"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES="1"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL="0"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success

  assert_output_not_contains "prod.your-site-domain.example"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: missing key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")

  unset VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY
  unset VORTEX_ACQUIA_KEY
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="prod"

  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  assert_failure

  assert_output_contains "Missing required value for VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY or VORTEX_ACQUIA_KEY."

  # Assert the guard stops the run before the token request. Without this, a key
  # reaching the script from any source turns the test into a live,
  # authenticated request to Acquia instead of a failed assertion.
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: reports the rejected credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"error":"invalid_client"}'
    '[FAIL] Authentication failed. Check VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY or VORTEX_ACQUIA_KEY and VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET or VORTEX_ACQUIA_SECRET. API response: {"error":"invalid_client"}'
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="prod"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: reports the missing application" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[]}}'
    '[FAIL] Application "testapp" not found. Check application name and access permissions.'
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="prod"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: reports the missing environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[]}}'
    '[FAIL] Environment "prod" not found in application "testapp". Check environment name.'
  )

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="prod"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}
