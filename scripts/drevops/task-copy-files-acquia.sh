#!/usr/bin/env bash
##
# Task to copy files between Acquia environments.
#
# It does not rely on 'drush ac-api-*' commands, which makes it capable of
# running on hosts without configured Drush and Drush aliases.
#
# It requires to have Cloud API token Key and Secret provided.
# To create your Cloud API token Acquia UI, go to
# Acquia Cloud UI -> Account -> API tokens -> Create Token
#
# @see https://cloudapi-docs.acquia.com/#/Environments/postEnvironmentsFiles

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Acquia Cloud API key.
DREVOPS_TASK_COPY_FILES_ACQUIA_KEY="${DREVOPS_TASK_COPY_FILES_ACQUIA_KEY:-${DREVOPS_ACQUIA_KEY}}"

# Acquia Cloud API secret.
DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET="${DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET:-${DREVOPS_ACQUIA_SECRET}}"

# Application name. Used to discover UUID.
DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME="${DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME:-${DREVOPS_ACQUIA_APP_NAME}}"

# Source environment name to copy from.
DREVOPS_TASK_COPY_FILES_ACQUIA_SRC="${DREVOPS_TASK_COPY_FILES_ACQUIA_SRC:-}"

# Destination environment name to copy to.
DREVOPS_TASK_COPY_FILES_ACQUIA_DST="${DREVOPS_TASK_COPY_FILES_ACQUIA_DST:-}"

# Number of status retrieval retries. If this limit reached and task has not
# yet finished, the task is considered failed.
DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES="${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES:-300}"

# Interval in seconds to check task status.
DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL="${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL:-10}"

#-------------------------------------------------------------------------------

echo "==> Started database copying from between environments in Acquia."

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
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_KEY}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_KEY." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_SRC}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_SRC." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_DST}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_DST." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES." && exit 1
[ -z "${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL}" ] && echo "ERROR: Missing value for DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL." && exit 1

echo "  > Retrieving authentication token."
token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${DREVOPS_TASK_COPY_FILES_ACQUIA_KEY}" --data-urlencode "client_secret=${DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
token=$(echo "${token_json}" | extract_json_value "access_token")
[ -z "${token}" ] && echo "ERROR: Unable to retrieve a token." && exit 1

echo "  > Retrieving ${DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME} application UUID."
app_uuid_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "https://cloud.acquia.com/api/applications?filter=name%3D${DREVOPS_TASK_COPY_FILES_ACQUIA_APP_NAME/ /%20}")
app_uuid=$(echo "${app_uuid_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "uuid")
[ -z "${app_uuid}" ] && echo "ERROR: Unable to retrieve an environment UUID." && exit 1

echo "  > Retrieving source environment ID."
envs_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "https://cloud.acquia.com/api/applications/${app_uuid}/environments?filter=name%3D${DREVOPS_TASK_COPY_FILES_ACQUIA_SRC}")
src_env_id=$(echo "${envs_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${src_env_id}" ] && echo "ERROR: Unable to retrieve source environment ID." && exit 1

echo "  > Retrieving destination environment ID."
envs_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "https://cloud.acquia.com/api/applications/${app_uuid}/environments?filter=name%3D${DREVOPS_TASK_COPY_FILES_ACQUIA_DST}")
dst_env_id=$(echo "${envs_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${dst_env_id}" ] && echo "ERROR: Unable to retrieve destination environment ID." && exit 1

echo "  > Copying files from ${DREVOPS_TASK_COPY_FILES_ACQUIA_SRC} to ${DREVOPS_TASK_COPY_FILES_ACQUIA_DST} environment."
task_status_json=$(curl -X POST -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" -H "Content-Type: application/json" -d "{\"source\":\"${src_env_id}\"}" "https://cloud.acquia.com/api/environments/${dst_env_id}/files")
notification_url=$(echo "${task_status_json}" | extract_json_value "_links" | extract_json_value "notification" | extract_json_value "href")

echo -n "  > Checking task status: "
task_completed=0
# shellcheck disable=SC2034
for i in $(seq 1 "${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_RETRIES}");
do
  echo -n "."
  sleep "${DREVOPS_TASK_COPY_FILES_ACQUIA_STATUS_INTERVAL}"
  task_status_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $token" "${notification_url}")
  task_state=$(echo "$task_status_json" | extract_json_value "status")
  [ "$task_state" = "completed" ] && task_completed=1 && break

  echo "  > Retrieving authentication token."
  token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${DREVOPS_TASK_COPY_FILES_ACQUIA_KEY}" --data-urlencode "client_secret=${DREVOPS_TASK_COPY_FILES_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
  token=$(echo "${token_json}" | extract_json_value "access_token")
  [ -z "${token}" ] && echo "ERROR: Unable to retrieve a token." && exit 1
done

echo

if [ "${task_completed}" = "0" ] ; then
  echo "ERROR: Unable to copy files from $DREVOPS_TASK_COPY_FILES_ACQUIA_SRC to $DREVOPS_TASK_COPY_FILES_ACQUIA_DST environment."
  exit 1
fi

echo "  > Successfully copied files from $DREVOPS_TASK_COPY_FILES_ACQUIA_SRC to $DREVOPS_TASK_COPY_FILES_ACQUIA_DST environment."

self_elapsed_time=$((SECONDS))
echo "  > Run duration: $((self_elapsed_time/60)) min $((self_elapsed_time%60)) sec."

echo "==> Finished files copying from between environments in Acquia."
