#!/usr/bin/env bats
##
# Unit tests for the 'vortex-task-purge-cache-acquia' script.
#
# shellcheck disable=SC2030,SC2031,SC2034,SC2016

load ../_helper.bash

# Appends the steps that resolve the token, application, environment and the
# domains attached to the environment.
#
# Arguments:
#   1. env_name: Environment to purge the cache for.
#   2. domains_json: Response of the environment domains API.
steps_lookup() {
  local env_name="${1}"
  local domains_json="${2}"

  STEPS+=(
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

    "Retrieving domains attached to ${env_name} environment."
    "@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/domains?limit=100&offset=0 # ${domains_json}"
  )
}

# Appends the request for a further page of the environment domains.
#
# Arguments:
#   1. offset: Offset the page is requested from.
#   2. domains_json: Response of the environment domains API.
steps_lookup_page() {
  local offset="${1}"
  local domains_json="${2}"

  STEPS+=(
    "@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/domains?limit=100&offset=${offset} # ${domains_json}"
  )
}

# Appends the steps of a purge that completes for one domain.
#
# Arguments:
#   1. env_name: Environment to purge the cache for.
#   2. domain: Domain to purge.
steps_purge() {
  local env_name="${1}"
  local domain="${2}"

  STEPS+=(
    "Purging cache for ${env_name} environment domain ${domain}."
    "@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {\"domains\":[\"${domain}\"]} https://cloud.acquia.com/api/environments/env-id-456/actions/clear-caches # {\"_links\":{\"notification\":{\"href\":\"https://cloud.acquia.com/api/notifications/notif-123\"}}}"
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"completed"}'
    "Purged cache for ${env_name} environment domain ${domain}."
    "[ OK ] Completed cache purging for ${env_name} environment domain ${domain}."
  )
}

# Builds a domains API response from a list of hostnames.
#
# A hostname starting with '*' is flagged as a wildcard.
#
# Arguments:
#   1. --total=<n>: Total reported in the pagination block. Optional, the
#      block is left out when omitted.
#   @: Hostnames attached to the environment.
domains_json() {
  local pagination=""
  if [ "${1#--total=}" != "${1:-}" ]; then
    pagination=",\"pagination\":{\"total\":${1#--total=},\"limit\":100,\"offset\":0}"
    shift
  fi

  local items=()
  local hostname
  local wildcard
  for hostname in "$@"; do
    wildcard="false"
    if [ "${hostname:0:1}" = "*" ]; then
      wildcard="true"
    fi
    items+=("{\"hostname\":\"${hostname}\",\"flags\":{\"default\":false,\"active\":true,\"wildcard\":${wildcard}}}")
  done

  local IFS=","
  echo "{\"total\":${#items[@]}${pagination},\"_embedded\":{\"items\":[${items[*]}]}}"
}

# Prints 100 hostnames, 'd<start>.example.com' onwards.
#
# Arguments:
#   1. start: Number of the first hostname.
full_page_hostnames() {
  local start="${1}"
  local i
  for ((i = start; i < start + 100; i++)); do
    echo "d${i}.example.com"
  done
}

# Runs the task for one environment with the steps in STEPS.
#
# Arguments:
#   1. env_name: Environment to purge the cache for.
#   2. domains_file: File to limit the purge to. Optional, the variable is left
#      unset when empty so the script default applies.
run_purge() {
  local env_name="${1}"
  local domains_file="${2:-}"

  export VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY="test-key"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET="test-secret"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME="testapp"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="${env_name}"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES="1"
  export VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL="0"

  unset VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE
  if [ -n "${domains_file}" ]; then
    export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${domains_file}"
  fi

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-purge-cache-acquia
  steps_run "assert" "${mocks[@]}"
}

# Asserts that a placeholder in the domains file resolves to the domain the
# environment has attached.
#
# Arguments:
#   1. env_name: Environment to purge the cache for.
#   2. line: Line written to the domains file.
#   3. domain: Domain the line is expected to resolve to.
assert_placeholder() {
  local env_name="${1}"
  local line="${2}"
  local domain="${3}"

  echo "${line}" >domains-placeholder.txt

  declare -a STEPS=()
  steps_lookup "${env_name}" "$(domains_json "${domain}" "other.example.com")"
  STEPS+=(
    "[ OK ] Retrieved 2 domains attached to ${env_name} environment."
    "Compiling a list of domains."
    "Limiting the purge to domains listed in domains-placeholder.txt."
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "${env_name}" "${domain}"
  STEPS+=(
    "- Purging cache for ${env_name} environment domain other.example.com."
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "${env_name}" "domains-placeholder.txt"

  assert_success
}

@test "task-purge-cache-acquia: purges every domain attached to the environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "sitedev.prod.acquia-sites.com" "dev.example.com" "*.example.com")"
  STEPS+=(
    "[ OK ] Retrieved 2 domains attached to dev environment."
    "Compiling a list of domains."
    "- Limiting the purge to domains listed in"
    "[ OK ] Compiled a list of 2 domains."
  )
  steps_purge "dev" "sitedev.prod.acquia-sites.com"
  steps_purge "dev" "dev.example.com"
  STEPS+=(
    "- *.example.com"
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: purges every domain attached to an on-demand environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "ode1" "$(domains_json "siteode1.prod.acquia-sites.com")"
  STEPS+=(
    "[ OK ] Retrieved 1 domains attached to ode1 environment."
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "ode1" "siteode1.prod.acquia-sites.com"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "ode1"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: skips a listed domain that is not attached and purges the rest" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf "dev.example.com\ndev.missing.example\nsitedev.prod.acquia-sites.com\n" >domains-list.txt

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "sitedev.prod.acquia-sites.com" "dev.example.com" "dev.other.example")"
  STEPS+=(
    "[ OK ] Retrieved 3 domains attached to dev environment."
    "Limiting the purge to domains listed in domains-list.txt."
    "Warning: Skipping domain dev.missing.example as it is not attached to dev environment."
    "[ OK ] Compiled a list of 2 domains."
  )
  steps_purge "dev" "dev.example.com"
  steps_purge "dev" "sitedev.prod.acquia-sites.com"
  STEPS+=(
    "- Purging cache for dev environment domain dev.missing.example."
    "- Purging cache for dev environment domain dev.other.example."
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev" "domains-list.txt"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: ignores blank lines, comments, whitespace and duplicates in the domains file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # The last line has no trailing newline.
  printf "# Domains to purge.\n\n  dev.example.com  \ndev.example.com\n\$TARGET_ENV_REMAP.example.com" >domains-format.txt

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "dev.example.com")"
  STEPS+=(
    "[ OK ] Retrieved 1 domains attached to dev environment."
    "- Warning: Skipping domain"
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "dev" "dev.example.com"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev" "domains-format.txt"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: prod strips the placeholder" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "prod" '$TARGET_ENV_REMAP.example.com' "example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: prod strips the legacy placeholder" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "prod" '$TARGET_ENV.example.com' "example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: dev prefixes the domain with the environment name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "dev" '$TARGET_ENV_REMAP.example.com' "dev.example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: test prefixes the domain with the stage name used in the UI" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "test" '$TARGET_ENV_REMAP.example.com' "stage.example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: test resolves the legacy placeholder to the environment name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "test" '$TARGET_ENV.example.com' "test.example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: test2 prefixes the domain with the environment name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  assert_placeholder "test2" '$TARGET_ENV_REMAP.example.com' "test2.example.com"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: does not evaluate the domains file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '$(touch evaluated.txt)dev.example.com\ndev.example.com\n' >domains-eval.txt

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "dev.example.com")"
  STEPS+=(
    'Warning: Skipping domain $(touch evaluated.txt)dev.example.com as it is not attached to dev environment.'
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "dev" "dev.example.com"

  run_purge "dev" "domains-eval.txt"

  assert_success
  assert_file_not_exists "evaluated.txt"

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: unknown environment skips placeholder lines and keeps plain domains" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '$TARGET_ENV_REMAP.example.com\node1.example.com\n' >domains-unknown.txt

  declare -a STEPS=()
  steps_lookup "ode1" "$(domains_json "siteode1.prod.acquia-sites.com" "ode1.example.com")"
  STEPS+=(
    'Skipping domain $TARGET_ENV_REMAP.example.com as its placeholder has no value for ode1 environment.'
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "ode1" "ode1.example.com"
  STEPS+=(
    "- Purging cache for ode1 environment domain siteode1.prod.acquia-sites.com."
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "ode1" "domains-unknown.txt"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: environment without domains purges nothing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "dev.example.com" >domains-none.txt

  declare -a STEPS=()
  steps_lookup "dev" '{"total":0,"_embedded":{"items":[]}}'
  STEPS+=(
    "[ OK ] Retrieved 0 domains attached to dev environment."
    "- Limiting the purge to domains listed in"
    "[ OK ] Compiled a list of 0 domains."
    "Unable to find domains to purge cache for dev environment."
    "- Purging cache for"
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev" "domains-none.txt"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: continues with the next domain when a purge is rejected" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "dev.example.com" "dev.other.example")"
  STEPS+=(
    "[ OK ] Compiled a list of 2 domains."

    "Purging cache for dev environment domain dev.example.com."
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"domains":["dev.example.com"]} https://cloud.acquia.com/api/environments/env-id-456/actions/clear-caches # {"error":"forbidden"}'
    'Warning: Unable to purge cache for dev environment domain dev.example.com. API response: {"error":"forbidden"}'
    "[ OK ] Completed cache purging for dev environment domain dev.example.com."
  )
  steps_purge "dev" "dev.other.example"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: continues with the next domain when the status checks run out" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "dev.example.com" "dev.other.example")"
  STEPS+=(
    "[ OK ] Compiled a list of 2 domains."

    "Purging cache for dev environment domain dev.example.com."
    '@curl -X POST -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token -H Content-Type: application/json -d {"domains":["dev.example.com"]} https://cloud.acquia.com/api/environments/env-id-456/actions/clear-caches # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notif-123"}}}'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notif-123 # {"status":"in-progress"}'
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'
    "Refreshed authentication token."
    "Warning: Unable to purge cache for dev environment domain dev.example.com."
    "[ OK ] Completed cache purging for dev environment domain dev.example.com."
  )
  steps_purge "dev" "dev.other.example"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: reports the failed domain lookup" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" '{"error":"not_found","message":"Domain names do not exist on non-hosted applications."}'
  STEPS+=(
    '[FAIL] Unable to retrieve domains attached to dev environment. API response: {"error":"not_found","message":"Domain names do not exist on non-hosted applications."}'
    "- Compiling a list of domains."
  )

  run_purge "dev"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: reports a domain lookup response without domains" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" '<html>Bad Gateway</html>'
  STEPS+=(
    '[FAIL] Unable to retrieve domains attached to dev environment. API response: <html>Bad Gateway</html>'
    "- Compiling a list of domains."
  )

  run_purge "dev"

  assert_failure

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: requests the next page of domains after a full page" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "d101.example.com" >domains-paged.txt

  mapfile -t first_page < <(full_page_hostnames 1)

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json "${first_page[@]}")"
  steps_lookup_page 100 "$(domains_json "d101.example.com")"
  STEPS+=(
    "[ OK ] Retrieved 101 domains attached to dev environment."
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "dev" "d101.example.com"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev" "domains-paged.txt"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: requests the next page of domains until the reported total" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=()
  steps_lookup "dev" "$(domains_json --total=3 "dev.example.com" "dev.other.example")"
  steps_lookup_page 2 "$(domains_json --total=3 "dev.third.example")"
  STEPS+=(
    "[ OK ] Retrieved 3 domains attached to dev environment."
    "[ OK ] Compiled a list of 3 domains."
  )
  steps_purge "dev" "dev.example.com"
  steps_purge "dev" "dev.other.example"
  steps_purge "dev" "dev.third.example"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-purge-cache-acquia: stops paging when the API repeats a page" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  echo "d1.example.com" >domains-repeated.txt

  mapfile -t first_page < <(full_page_hostnames 1)
  page_json="$(domains_json "${first_page[@]}")"

  declare -a STEPS=()
  steps_lookup "dev" "${page_json}"
  steps_lookup_page 100 "${page_json}"
  STEPS+=(
    "[ OK ] Retrieved 100 domains attached to dev environment."
    "[ OK ] Compiled a list of 1 domains."
  )
  steps_purge "dev" "d1.example.com"
  STEPS+=(
    "[ OK ] Finished cache purging in Acquia."
  )

  run_purge "dev" "domains-repeated.txt"

  assert_success

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
