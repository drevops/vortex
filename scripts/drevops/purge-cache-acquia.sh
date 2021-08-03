#!/usr/bin/env bash
##
# Flush Varnish cache in the Acquia site environment.
# @see https://cloudapi-docs.acquia.com/#/Environments/postEnvironmentsDomainsClearVarnish

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

#-------------------------------------------------------------------------------
#                             REQUIRED VARIABLES
#-------------------------------------------------------------------------------

# Application name. Used to discover UUID.
AC_API_APP_NAME="${AC_API_APP_NAME:-}"
# Source environment name to clear Varnish cache for.
AC_API_VARNISH_ENV="${AC_API_VARNISH_ENV:-}"
# File with a list of domains that should be cleared.
AC_API_VARNISH_DOMAINS_FILE="${AC_API_VARNISH_DOMAINS_FILE:-domains.txt}"

#-------------------------------------------------------------------------------
#                              OPTIONAL VARIABLES
#-------------------------------------------------------------------------------

# Both user key and secret are read from environment variables or .env.local
# file. They should be provided through environment variables only in
# environments that do not have .env.local file created (usually, non-local
# environments).
AC_API_KEY="${AC_API_KEY:-}"
AC_API_SECRET="${AC_API_SECRET:-}"

# Location of the file with credentials.
AC_CREDENTIALS_FILE=${AC_CREDENTIALS_FILE:-.env.local}

# Number of status retrieval retries. If this limit reached and task has not
# yet finished, the task is considered failed.
AC_API_STATUS_RETRIES="${AC_API_STATUS_RETRIES:-300}"
# Interval in seconds to check task status.
AC_API_STATUS_INTERVAL="${AC_API_STATUS_INTERVAL:-10}"

#-------------------------------------------------------------------------------
#                       DO NOT CHANGE ANYTHING BELOW THIS LINE
#-------------------------------------------------------------------------------

#
# Function to extract last value from JSON object passed via STDIN.
#
extract_json_last_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print trim(json_encode(\$last[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

# Pre-flight checks.
command -v curl > /dev/null || ( echo "ERROR: curl command is not available." && exit 1 )

# Try to read credentials from the credentials file.
if [ -z "${AC_API_KEY}" ] && [ -f "${AC_CREDENTIALS_FILE}" ]; then
  # shellcheck disable=SC1090
  t=$(mktemp) && export -p > "$t" && set -a && . "${AC_CREDENTIALS_FILE}" && set +a && . "$t" && rm "$t" && unset t
fi

# Check that all required variables are present.
[ -z "${AC_API_APP_NAME}" ] && echo "ERROR: Missing value for AC_API_APP_NAME." && exit 1
[ -z "${AC_API_VARNISH_ENV}" ] && echo "ERROR: Missing value for AC_API_VARNISH_ENV." && exit 1
[ -z "${AC_API_VARNISH_DOMAINS_FILE}" ] && echo "ERROR: Missing value for AC_API_VARNISH_DOMAINS_FILE." && exit 1
[ -z "${AC_API_KEY}" ] && echo "ERROR: Missing value for AC_API_KEY." && exit 1
[ -z "${AC_API_SECRET}" ] && echo "ERROR: Missing value for AC_API_SECRET." && exit 1

echo "==> Retrieving authentication token."
TOKEN_JSON=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${AC_API_KEY}" --data-urlencode "client_secret=${AC_API_SECRET}" --data-urlencode "grant_type=client_credentials")
TOKEN=$(echo "${TOKEN_JSON}" | extract_json_value "access_token")
[ -z "${TOKEN}" ] && echo "ERROR: Unable to retrieve a token." && exit 1

echo "==> Retrieving ${AC_API_APP_NAME} application UUID."
APP_UUID_JSON=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "https://cloud.acquia.com/api/applications?filter=name%3D${AC_API_APP_NAME/ /%20}")
APP_UUID=$(echo "${APP_UUID_JSON}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "uuid")
[ -z "${APP_UUID}" ] && echo "ERROR: Unable to retrieve an environment UUID." && exit 1

echo "==> Retrieving environment id."
ENVS_JSON=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "https://cloud.acquia.com/api/applications/${APP_UUID}/environments?filter=name%3D${AC_API_VARNISH_ENV}")
ENV_ID=$(echo "${ENVS_JSON}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${ENV_ID}" ] && echo "ERROR: Unable to retrieve environment ID." && exit 1

echo "==> Compiling a list of domains."

TARGET_ENV="${AC_API_VARNISH_ENV}"
DOMAINS_LIST=()
while read -r DOMAIN; do
  # Special variable to remap target env to the sub-domain prefix based on UI name.
  TARGET_ENV_REMAP="${TARGET_ENV}"
  # Strip placeholder for PROD environment.
  if [ "${TARGET_ENV}" == "prod" ] ; then
    DOMAIN="${DOMAIN//\$TARGET_ENV_REMAP./}"
    DOMAIN="${DOMAIN//\$TARGET_ENV./}"
  fi

  # Re-map 'test' to 'stage' as seen in UI.
  if [ "${TARGET_ENV}" == "test" ] ; then
    TARGET_ENV_REMAP=stage
  fi

  # Disable replacement for unknown environments.
  if [ "${TARGET_ENV}" != "dev" ] && [ "${TARGET_ENV}" != "test" ] && [ "${TARGET_ENV}" != "test2" ] && [ "${TARGET_ENV}" != "prod" ]; then
    TARGET_ENV_REMAP=""
  fi

  # Proceed only if the environment was provided.
  if [ "${TARGET_ENV_REMAP}" != "" ] ; then
    # Interpolate variables in domain name.
    DOMAIN="$(eval echo "${DOMAIN}")"
    # Add domain to list.
    DOMAINS_LIST+=("${DOMAIN}")
  fi
done < "${AC_API_VARNISH_DOMAINS_FILE}"

if [ "${#DOMAINS_LIST[@]}" -gt 0 ]; then
  # Acquia API stops clearing Varnish caches if at least 1 domain fails, so
  # we are clearing caches for every domain separately and not failing if
  # the domain is not found.
  for domain in "${DOMAINS_LIST[@]}"; do
    echo "==> Flushing Varnish cache for ${AC_API_VARNISH_ENV} environment domain ${domain}."
    TASK_STATUS_JSON=$(curl -X POST -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "{\"domains\":[\"${domain}\"]}" "https://cloud.acquia.com/api/environments/${ENV_ID}/domains/actions/clear-varnish")
    NOTIFICATION_URL=$(echo "${TASK_STATUS_JSON}" | extract_json_value "_links" | extract_json_value "notification" | extract_json_value "href") || true

    # If domain does not exist - notification will be empty; we are skipping
    # non-existing domains without a failure.
    if [ "${NOTIFICATION_URL}" == "" ]; then
      echo "==> Unable to flush Varnish for ${AC_API_VARNISH_ENV} environment domain ${domain} as it does not exist."
      break;
    fi

    echo -n "==> Checking task status: "
    TASK_COMPLETED=0
    # shellcheck disable=SC2034
    for i in $(seq 1 "${AC_API_STATUS_RETRIES}");
    do
      echo -n "."
      sleep "${AC_API_STATUS_INTERVAL}"
      TASK_STATUS_JSON=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "${NOTIFICATION_URL}")
      TASK_STATE=$(echo "$TASK_STATUS_JSON" | extract_json_value "status")
      if [ "$TASK_STATE" == "completed" ]; then
        echo "==> Successfully flushed Varnish cache for ${AC_API_VARNISH_ENV} environment domain ${domain}."
        TASK_COMPLETED=1;
        break 1;
      fi

      echo "==> Retrieving authentication token."
      TOKEN_JSON=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${AC_API_KEY}" --data-urlencode "client_secret=${AC_API_SECRET}" --data-urlencode "grant_type=client_credentials")
      TOKEN=$(echo "${TOKEN_JSON}" | extract_json_value "access_token")
      [ -z "${TOKEN}" ] && echo "ERROR: Unable to retrieve a token." && exit 1
    done
    echo

    if [ "${TASK_COMPLETED}" == "0" ] ; then
      echo "==> Unable to flush Varnish for ${AC_API_VARNISH_ENV} environment domain ${domain}."
    fi
  done;
else
  echo "==> Unable to find domains to flush Varnish for ${AC_API_VARNISH_ENV} environment."
fi

SELF_ELAPSED_TIME=$((SECONDS))
echo "==> Build duration: $((SELF_ELAPSED_TIME/60)) min $((SELF_ELAPSED_TIME%60)) sec."
