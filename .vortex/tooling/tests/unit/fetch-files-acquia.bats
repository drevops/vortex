#!/usr/bin/env bats
#
# Unit tests for the 'vortex-fetch-files-acquia' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the variables required by the script.
export_required_variables() {
  export VORTEX_FETCH_FILES_ACQUIA_KEY="test-key"
  export VORTEX_FETCH_FILES_ACQUIA_SECRET="test-secret"
  export VORTEX_FETCH_FILES_ACQUIA_APP_NAME="testapp"
  export VORTEX_FETCH_FILES_ENVIRONMENT="prod"
  export VORTEX_FETCH_FILES_DIR="files"
}

@test "fetch-files-acquia: Fetch files successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf files

  declare -a STEPS=(
    "[INFO] Started files fetch from Acquia."

    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    "[ OK ] Retrieved authentication token."

    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'
    "[ OK ] Retrieved testapp application UUID."

    "[TASK] Retrieving prod environment SSH address."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod","ssh_url":"testapp.prod@testappprod.ssh.prod.acquia-sites.com"}]}}'
    "[ OK ] Retrieved prod environment SSH address."

    "[INFO] Started SSH setup."

    "[TASK] Fetching files from prod environment."
    '@rsync -rltz --exclude /css --exclude /js --exclude /php --exclude /styles -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes testapp.prod@testappprod.ssh.prod.acquia-sites.com:/mnt/files/testapp.prod/sites/default/files/ files/ # 0 #  # echo "image" > files/photo.jpg'
    "[ OK ] Fetched files from prod environment."

    "[ OK ] Finished files fetch from Acquia."
  )

  export_required_variables

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success
  assert_file_contains "files/photo.jpg" "image"

  popd >/dev/null
}

@test "fetch-files-acquia: Fall back to shared credentials and the database environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=shared-key --data-urlencode client_secret=shared-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dsharedapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"sharedapp"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest # {"_embedded":{"items":[{"id":"env-id-456","name":"test","ssh_url":"sharedapp.test@sharedapptest.ssh.prod.acquia-sites.com"}]}}'
    "[TASK] Fetching files from test environment."
    '@rsync -rltz -e ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o LogLevel=error -o IdentitiesOnly=yes sharedapp.test@sharedapptest.ssh.prod.acquia-sites.com:/var/custom/files/ files/ # 0'
    "[ OK ] Finished files fetch from Acquia."
  )

  unset VORTEX_FETCH_FILES_ACQUIA_KEY VORTEX_FETCH_FILES_ACQUIA_SECRET VORTEX_FETCH_FILES_ACQUIA_APP_NAME VORTEX_FETCH_FILES_ENVIRONMENT
  export VORTEX_ACQUIA_KEY="shared-key"
  export VORTEX_ACQUIA_SECRET="shared-secret"
  export VORTEX_ACQUIA_APP_NAME="sharedapp"
  export VORTEX_FETCH_DB_ENVIRONMENT="test"
  export VORTEX_FETCH_FILES_ACQUIA_REMOTE_DIR="/var/custom/files"
  export VORTEX_FETCH_FILES_EXCLUDE=""
  export VORTEX_FETCH_FILES_DIR="files"

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null
}

@test "fetch-files-acquia: Fail when required values are missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_ACQUIA_KEY VORTEX_ACQUIA_SECRET VORTEX_ACQUIA_APP_NAME VORTEX_FETCH_DB_ENVIRONMENT

  export_required_variables
  export VORTEX_FETCH_FILES_ACQUIA_KEY=""
  run .vortex/tooling/src/vortex-fetch-files-acquia
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_FILES_ACQUIA_KEY or VORTEX_ACQUIA_KEY."

  export_required_variables
  export VORTEX_FETCH_FILES_ACQUIA_SECRET=""
  run .vortex/tooling/src/vortex-fetch-files-acquia
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_FILES_ACQUIA_SECRET or VORTEX_ACQUIA_SECRET."

  export_required_variables
  export VORTEX_FETCH_FILES_ACQUIA_APP_NAME=""
  run .vortex/tooling/src/vortex-fetch-files-acquia
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_FILES_ACQUIA_APP_NAME or VORTEX_ACQUIA_APP_NAME."

  export_required_variables
  export VORTEX_FETCH_FILES_ENVIRONMENT=""
  # The fixture '.env' sets the database environment, so it is overridden.
  export VORTEX_FETCH_DB_ENVIRONMENT=""
  run .vortex/tooling/src/vortex-fetch-files-acquia
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_FILES_ENVIRONMENT or VORTEX_FETCH_DB_ENVIRONMENT."

  popd >/dev/null
}

@test "fetch-files-acquia: Authentication failure with error response" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"error":"invalid_client","error_description":"Client authentication failed"}'
    "[FAIL] Authentication failed. Check VORTEX_FETCH_FILES_ACQUIA_KEY or VORTEX_ACQUIA_KEY and VORTEX_FETCH_FILES_ACQUIA_SECRET or VORTEX_ACQUIA_SECRET."
  )

  export_required_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "fetch-files-acquia: Application not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[]}}'
    '[FAIL] Application "testapp" not found. Check application name and access permissions.'
  )

  export_required_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "fetch-files-acquia: Environment not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[]}}'
    '[FAIL] Environment "prod" not found in application "testapp". Check environment name.'
  )

  export_required_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "fetch-files-acquia: Fail when the environment has no SSH address" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'
    '[FAIL] Unable to retrieve SSH address for "prod" environment.'
    "- Fetching files from prod environment."
  )

  export_required_variables

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "fetch-files-acquia: Fail when rsync fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod","ssh_url":"testapp.prod@testappprod.ssh.prod.acquia-sites.com"}]}}'
    "[TASK] Fetching files from prod environment."
    "@rsync * # 23"
    "- Fetched files from prod environment."
    "- Finished files fetch from Acquia."
  )

  export_required_variables

  fixture_ssh_key_prepare
  export VORTEX_FETCH_FILES_SSH_FILE=false

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-fetch-files-acquia
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}
