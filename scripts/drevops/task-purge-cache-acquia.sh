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

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Acquia Cloud API key.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY:-${DREVOPS_ACQUIA_KEY}}"

# Acquia Cloud API secret.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET:-${DREVOPS_ACQUIA_SECRET}}"

# Application name. Used to discover UUID.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME:-}"

# An environment name to purge cache for.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV:-}"

# File with a list of domains that should be purged.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE:-domains.txt}"

# Number of status retrieval retries. If this limit reached and task has not
# yet finished, the task is considered failed.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES:-300}"

# Interval in seconds to check task status.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL:-10}"

#-------------------------------------------------------------------------------

echo "==> Started cache purging in Acquia."

#
# Extract last value from JSON object passed via STDIN.
#
extract_json_last_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print trim(json_encode(\$last[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

# Pre-flight checks.
command -v curl > /dev/null || ( echo "ERROR: curl command is not available." && exit 1 )

# Check that all required variables are present.
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES." && exit 1
[ -z "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL}" ] && echo "ERROR: Missing value for DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL." && exit 1

echo "  > Retrieving authentication token."
token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY}" --data-urlencode "client_secret=${DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
token=$(echo "${token_json}" | extract_json_value "access_token")
[ -z "${token}" ] && echo "ERROR: Unable to retrieve a token." && exit 1

echo "  > Retrieving ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME} application UUID."
app_uuid_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "https://cloud.acquia.com/api/applications?filter=name%3D${DREVOPS_TASK_PURGE_CACHE_ACQUIA_APP_NAME/ /%20}")
app_uuid=$(echo "${app_uuid_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "uuid")
[ -z "${app_uuid}" ] && echo "ERROR: Unable to retrieve an environment UUID." && exit 1

echo "  > Retrieving environment ID."
envs_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "https://cloud.acquia.com/api/applications/${app_uuid}/environments?filter=name%3D${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV}")
ENV_ID=$(echo "${envs_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${ENV_ID}" ] && echo "ERROR: Unable to retrieve environment ID." && exit 1

echo "  > Compiling a list of domains."

target_env="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV}"
domain_list=()
while read -r domain; do
  # Special variable to remap target env to the sub-domain prefix based on UI name.
  target_env_remap="${target_env}"
  # Strip placeholder for PROD environment.
  if [ "${target_env}" = "prod" ] ; then
    domain="${domain//\$target_env_remap./}"
    domain="${domain//\$target_env./}"
  fi

  # Re-map 'test' to 'stage' as seen in UI.
  if [ "${target_env}" = "test" ] ; then
    target_env_remap=stage
  fi

  # Disable replacement for unknown environments.
  if [ "${target_env}" != "dev" ] && [ "${target_env}" != "test" ] && [ "${target_env}" != "test2" ] && [ "${target_env}" != "prod" ]; then
    target_env_remap=""
  fi

  # Proceed only if the environment was provided.
  if [ "${target_env_remap}" != "" ] ; then
    # Interpolate variables in domain name.
    domain="$(eval echo "${domain}")"
    # Add domain to list.
    domain_list+=("${domain}")
  fi
done < "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE}"

if [ "${#domain_list[@]}" -gt 0 ]; then
  # Acquia API stops clearing purging caches if at least 1 domain fails, so
  # we are clearing caches for every domain separately and not failing if
  # the domain is not found.
  for domain in "${domain_list[@]}"; do
    echo "  > Purging cache for ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
    task_status_json=$(curl -X POST -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" -H "Content-Type: application/json" -d "{\"domains\":[\"${domain}\"]}" "https://cloud.acquia.com/api/environments/${ENV_ID}/domains/actions/clear-varnish")
    notification_url=$(echo "${task_status_json}" | extract_json_value "_links" | extract_json_value "notification" | extract_json_value "href") || true

    # If domain does not exist - notification will be empty; we are skipping
    # non-existing domains without a failure.
    if [ "${notification_url}" = "" ]; then
      echo "  > Warning: Unable to purge cache for ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain} as it does not exist."
      break;
    fi

    echo -n "  > Checking task status: "
    task_completed=0
    # shellcheck disable=SC2034
    for i in $(seq 1 "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES}");
    do
      echo -n "."
      sleep "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL}"
      task_status_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "${notification_url}")
      task_state=$(echo "$task_status_json" | extract_json_value "status")
      if [ "$task_state" = "completed" ]; then
        echo "  > Successfully purged cache for ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
        task_completed=1;
        break 1;
      fi

      echo "  > Retrieving authentication token."
      token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${DREVOPS_TASK_PURGE_CACHE_ACQUIA_KEY}" --data-urlencode "client_secret=${DREVOPS_TASK_PURGE_CACHE_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
      token=$(echo "${token_json}" | extract_json_value "access_token")
      [ -z "${token}" ] && echo "ERROR: Unable to retrieve a token." && exit 1
    done
    echo

    if [ "${task_completed}" = "0" ] ; then
      echo "  > Warning: Unable to purge cache for ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV} environment domain ${domain}."
    fi
  done;
else
  echo "  > Unable to find domains to purge cache for ${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV} environment."
fi

self_elapsed_time=$((SECONDS))
echo "  > Run duration: $((self_elapsed_time/60)) min $((self_elapsed_time%60)) sec."

echo "==> Finished cache purging in Acquia."
