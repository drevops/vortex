#!/usr/bin/env bash
##
# Task to purge edge cache in the Acquia site environment.
#
# It does not rely on 'drush ac-api-*' commands, which makes it capable of
# running on hosts without configured Drush and Drush aliases.
#
# It requires to have Cloud API token Key and Secret provided.
# To create your Cloud API token Acquia UI, go to
# Acquia Cloud UI -> Account -> API tokens -> Create Token
#
# @see https://cloudapi-docs.acquia.com/#/Environments/postEnvironmentsDomainsClearVarnish
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Acquia Cloud API key.
VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY:-${VORTEX_ACQUIA_KEY}}"

# Acquia Cloud API secret.
VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET:-${VORTEX_ACQUIA_SECRET}}"

# Application name. Used to discover UUID.
VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-}"

# An environment name to purge cache for.
VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV:-}"

# File with a list of domains that should be purged.
VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE:-domains.txt}"

# Number of status retrieval retries. If this limit reached and task has not
# yet finished, the task is considered failed.
VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES="${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES:-300}"

# Interval in seconds to check task status.
VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL="${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL:-10}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

# Pre-flight checks.
for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started cache purging in Acquia."

#
# Extract last value from JSON object passed via STDIN.
#
extract_json_last_value() {
  local key="${1}"
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print trim(json_encode(\$last[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key="${1}"
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

# Check that all required variables are present.
[ -z "${VORTEX_ACQUIA_KEY}" ] && fail "Missing value for VORTEX_ACQUIA_KEY." && exit 1
[ -z "${VORTEX_ACQUIA_SECRET}" ] && fail "Missing value for VORTEX_ACQUIA_SECRET." && exit 1
[ -z "${VORTEX_ACQUIA_APP_NAME}" ] && fail "Missing value for VORTEX_ACQUIA_APP_NAME." && exit 1
[ -z "${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV}" ] && fail "Missing value for VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV." && exit 1
[ -z "${VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE}" ] && fail "Missing value for VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE." && exit 1
[ -z "${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES}" ] && fail "Missing value for VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES." && exit 1
[ -z "${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL}" ] && fail "Missing value for VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL." && exit 1

note "Retrieving authentication token."
token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${VORTEX_ACQUIA_KEY}" --data-urlencode "client_secret=${VORTEX_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
token=$(echo "${token_json}" | extract_json_value "access_token")
[ -z "${token}" ] && fail "Unable to retrieve a token." && exit 1

note "Retrieving ${VORTEX_ACQUIA_APP_NAME} application UUID."
app_uuid_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/applications?filter=name%3D${VORTEX_ACQUIA_APP_NAME/ /%20}")
app_uuid=$(echo "${app_uuid_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "uuid")
[ -z "${app_uuid}" ] && fail "Unable to retrieve an environment UUID." && exit 1

note "Retrieving environment ID."
envs_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/applications/${app_uuid}/environments?filter=name%3D${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV}")
ENV_ID=$(echo "${envs_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${ENV_ID}" ] && fail "Unable to retrieve environment ID." && exit 1

note "Compiling a list of domains."

target_env="${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV}"
domain_list=()
while read -r domain; do
  # Special variable to remap target env to the sub-domain prefix based on UI name.
  TARGET_ENV_REMAP="${target_env}"
  # Strip placeholder for PROD environment.
  if [ "${target_env}" = "prod" ]; then
    domain="${domain//\$target_env_remap./}"
    domain="${domain//\$target_env./}"
  fi

  # Re-map 'test' to 'stage' as seen in UI.
  if [ "${target_env}" = "test" ]; then
    TARGET_ENV_REMAP=stage
  fi

  # Disable replacement for unknown environments.
  if [ "${target_env}" != "dev" ] && [ "${target_env}" != "test" ] && [ "${target_env}" != "test2" ] && [ "${target_env}" != "prod" ]; then
    TARGET_ENV_REMAP=""
  fi

  # Proceed only if the environment was provided.
  if [ "${TARGET_ENV_REMAP}" != "" ]; then
    # Interpolate variables in domain name.
    domain="$(eval echo "${domain}")"
    # Add domain to list.
    domain_list+=("${domain}")
  fi
done <"${VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE}"

if [ "${#domain_list[@]}" -gt 0 ]; then
  # Acquia API stops clearing purging caches if at least 1 domain fails, so
  # we are clearing caches for every domain separately and not failing if
  # the domain is not found.
  for domain in "${domain_list[@]}"; do
    note "Purging cache for ${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
    task_status_json=$(curl -X POST -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" -H "Content-Type: application/json" -d "{\"domains\":[\"${domain}\"]}" "https://cloud.acquia.com/api/environments/${ENV_ID}/domains/actions/clear-varnish")
    notification_url=$(echo "${task_status_json}" | extract_json_value "_links" | extract_json_value "notification" | extract_json_value "href") || true

    # If domain does not exist - notification will be empty; we are skipping
    # non-existing domains without a failure.
    if [ "${notification_url}" = "" ]; then
      note "Warning: Unable to purge cache for ${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain} as it does not exist."
      break
    fi

    echo -n "     > Checking task status: "
    task_completed=0
    # shellcheck disable=SC2034
    for i in $(seq 1 "${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES}"); do
      echo -n "."
      sleep "${VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL}"
      task_status_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "${notification_url}")
      task_state=$(echo "${task_status_json}" | extract_json_value "status")
      if [ "${task_state}" = "completed" ]; then
        note "Purged cache for ${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
        task_completed=1
        break 1
      fi

      note "Retrieving authentication token."
      token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${VORTEX_ACQUIA_KEY}" --data-urlencode "client_secret=${VORTEX_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
      token=$(echo "${token_json}" | extract_json_value "access_token")
      [ -z "${token}" ] && fail "Unable to retrieve a token." && exit 1
    done
    echo

    if [ "${task_completed}" = "0" ]; then
      note "Warning: Unable to purge cache for ${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
    fi
  done
else
  note "Unable to find domains to purge cache for ${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV} environment."
fi

self_elapsed_time=$((SECONDS))
note "Run duration: $((self_elapsed_time / 60)) min $((self_elapsed_time % 60)) sec."

pass "Finished cache purging in Acquia."
