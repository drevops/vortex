#!/usr/bin/env bash
##
# Download DB dump from the latest Acquia Cloud backup.
#
# This script will discover the latest available backup in the specified
# Acquia Cloud environment using Acquia Cloud API 2.0, download and decompress
# it into specified directory.
#
# It does not rely on 'drush ac-api-*' commands, which makes it capable of
# running on hosts without configured Drush and Drush aliases.
#
# It requires to have Cloud API token Key and Secret provided.
# To create your Cloud API token Acquia UI, go to
# Acquia Cloud UI -> Account -> API tokens -> Create Token
#
# @see https://docs.acquia.com/acquia-cloud/develop/api/auth/#cloud-generate-api-token
# @see https://cloudapi-docs.acquia.com/#/Environments/getEnvironmentsDatabaseDownloadBackup
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Acquia Cloud API key.
VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY:-}"

# Acquia Cloud API secret.
VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET:-}"

# Application name. Used to discover UUID.
VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-}"

# Source environment name used to download the database dump from.
VORTEX_DB_DOWNLOAD_ENVIRONMENT="${VORTEX_DB_DOWNLOAD_ENVIRONMENT:-}"

# Database name within source environment used to download the database dump.
VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME:-}"

# Directory where DB dumps are stored.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Database dump file name.
VORTEX_DB_FILE="${VORTEX_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl gunzip; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started database dump download from Acquia."

#
# Extract last value from JSON object passed via STDIN.
#
extract_json_last_value() {
  local key=${1}
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print trim(json_encode(\$last[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=${1}
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

# Check that all required variables are present.
[ -z "${VORTEX_ACQUIA_KEY}" ] && fail "Missing value for VORTEX_ACQUIA_KEY." && exit 1
[ -z "${VORTEX_ACQUIA_SECRET}" ] && fail "Missing value for VORTEX_ACQUIA_SECRET." && exit 1
[ -z "${VORTEX_ACQUIA_APP_NAME}" ] && fail "Missing value for VORTEX_ACQUIA_APP_NAME." && exit 1
[ -z "${VORTEX_DB_DOWNLOAD_ENVIRONMENT}" ] && fail "Missing value for VORTEX_DB_DOWNLOAD_ENVIRONMENT." && exit 1
[ -z "${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}" ] && fail "Missing value for VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME." && exit 1

mkdir -p "${VORTEX_DB_DIR}"

task "Retrieving authentication token."
token_json=$(curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${VORTEX_ACQUIA_KEY}" --data-urlencode "client_secret=${VORTEX_ACQUIA_SECRET}" --data-urlencode "grant_type=client_credentials")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Token API response: ${token_json}"

# Check for HTTP errors in response
if echo "${token_json}" | grep -q '"error"'; then
  fail "Authentication failed. Check VORTEX_ACQUIA_KEY and VORTEX_ACQUIA_SECRET. API response: ${token_json}"
  exit 1
fi

token="$(echo "${token_json}" | extract_json_value "access_token")"
[ "${VORTEX_DEBUG-}" = "1" ] && note "Extracted token: ${token}"
[ -z "${token}" ] && fail "Unable to retrieve a token. API response: ${token_json}" && exit 1

task "Retrieving ${VORTEX_ACQUIA_APP_NAME} application UUID."
app_uuid_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/applications?filter=name%3D${VORTEX_ACQUIA_APP_NAME/ /%20}")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Application API response: ${app_uuid_json}"

# Check for empty items array (application not found)
if echo "${app_uuid_json}" | grep -q '"items":\[\]'; then
  fail "Application '${VORTEX_ACQUIA_APP_NAME}' not found. Check application name and access permissions."
  exit 1
fi

app_uuid=$(echo "${app_uuid_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "uuid")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Extracted app UUID: ${app_uuid}"
[ -z "${app_uuid}" ] && fail "Unable to retrieve an application UUID for '${VORTEX_ACQUIA_APP_NAME}'. API response: ${app_uuid_json}" && exit 1

task "Retrieving ${VORTEX_DB_DOWNLOAD_ENVIRONMENT} environment ID."
envs_json=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/applications/${app_uuid}/environments?filter=name%3D${VORTEX_DB_DOWNLOAD_ENVIRONMENT}")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Environments API response: ${envs_json}"

# Check for empty items array (environment not found)
if echo "${envs_json}" | grep -q '"items":\[\]'; then
  fail "Environment '${VORTEX_DB_DOWNLOAD_ENVIRONMENT}' not found in application '${VORTEX_ACQUIA_APP_NAME}'. Check environment name."
  exit 1
fi

env_id=$(echo "${envs_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Extracted environment ID: ${env_id}"
[ -z "${env_id}" ] && fail "Unable to retrieve an environment ID for '${VORTEX_DB_DOWNLOAD_ENVIRONMENT}'. API response: ${envs_json}" && exit 1

task "Discovering latest backup ID for DB ${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}."
backups_json=$(curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/environments/${env_id}/databases/${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}/backups?sort=created")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Backups API response: ${backups_json}"

# Check for HTTP errors or database not found
if echo "${backups_json}" | grep -q '"error"'; then
  fail "Database '${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}' not found in environment '${VORTEX_DB_DOWNLOAD_ENVIRONMENT}'. Check database name. API response: ${backups_json}"
  exit 1
fi

# Check for empty items array (no backups found)
if echo "${backups_json}" | grep -q '"items":\[\]'; then
  fail "No backups found for database '${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}' in environment '${VORTEX_DB_DOWNLOAD_ENVIRONMENT}'. Try creating a backup first."
  exit 1
fi

# Acquia response has all backups sorted chronologically by created date.
backup_id=$(echo "${backups_json}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ "${VORTEX_DEBUG-}" = "1" ] && note "Extracted backup ID: ${backup_id}"
[ -z "${backup_id}" ] && fail "Unable to discover backup ID for DB '${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}'. API response: ${backups_json}" && exit 1

# Insert backup id as a suffix.
file_extension="${VORTEX_DB_FILE##*.}"
file_prefix="${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}_backup_"
file_name="${VORTEX_DB_DIR}/${file_prefix}${backup_id}.${file_extension}"
file_name_discovered="${file_name}"
file_name_compressed="${file_name}.gz"

[ "${VORTEX_DEBUG-}" = "1" ] && note "Target file name: ${file_name}"
[ "${VORTEX_DEBUG-}" = "1" ] && note "Compressed file name: ${file_name_compressed}"

if [ -f "${file_name_discovered}" ]; then
  note "Found existing cached DB file \"${file_name_discovered}\" for DB \"${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}\"."
else
  # If the gzipped version exists, then we don't need to re-download it.
  if [ ! -f "${file_name_compressed}" ]; then
    note "Using the latest backup ID ${backup_id} for DB ${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}."

    [ ! -d "${VORTEX_DB_DIR:-}" ] && note "Creating dump directory ${VORTEX_DB_DIR}" && mkdir -p "${VORTEX_DB_DIR}"

    task "Discovering backup URL."
    backup_json=$(curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "https://cloud.acquia.com/api/environments/${env_id}/databases/${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}/backups/${backup_id}/actions/download")
    [ "${VORTEX_DEBUG-}" = "1" ] && note "Backup URL API response: ${backup_json}"
    backup_url=$(echo "${backup_json}" | extract_json_value "url")
    [ "${VORTEX_DEBUG-}" = "1" ] && note "Extracted backup URL: ${backup_url}"
    [ -z "${backup_url}" ] && fail "Unable to discover backup URL for backup ID '${backup_id}'. API response: ${backup_json}" && exit 1

    task "Downloading DB dump into file ${file_name_compressed}."
    curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer ${token}" "${backup_url}" -o "${file_name_compressed}"
    download_result=$?

    # shellcheck disable=SC2181
    [ "${download_result}" -ne 0 ] && fail "Unable to download database ${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}. curl exit code: ${download_result}" && exit 1

    # Check if the downloaded file exists and has content
    if [ ! -f "${file_name_compressed}" ] || [ ! -s "${file_name_compressed}" ]; then
      fail "Downloaded file is empty or missing: ${file_name_compressed}"
      rm -f "${file_name_compressed}"
      exit 1
    fi

    [ "${VORTEX_DEBUG-}" = "1" ] && note "Download completed successfully"
  else
    pass "Found existing cached gzipped DB file ${file_name_compressed} for DB ${VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME}."
  fi

  task "Expanding DB file ${file_name_compressed} into ${file_name}."
  [ "${VORTEX_DEBUG-}" = "1" ] && note "Starting decompression of ${file_name_compressed}"

  # Test the gzip file first to ensure it's valid
  if ! gunzip -t "${file_name_compressed}" 2>/dev/null; then
    fail "Downloaded file is not a valid gzip archive: ${file_name_compressed}"
    rm -f "${file_name_compressed}"
    exit 1
  fi

  gunzip -c "${file_name_compressed}" >"${file_name}"
  decompress_result=$?
  [ "${VORTEX_DEBUG-}" = "1" ] && note "Decompression result: ${decompress_result}"
  rm "${file_name_compressed}"

  # Check decompression result and file validity
  if [ "${decompress_result}" != 0 ] || [ ! -f "${file_name}" ] || [ ! -s "${file_name}" ]; then
    fail "Unable to process DB dump file \"${file_name}\". Decompression exit code: ${decompress_result}"
    rm -f "${file_name_compressed}" "${file_name}"
    exit 1
  fi

  [ "${VORTEX_DEBUG-}" = "1" ] && note "Decompression completed successfully"
fi

task "Renaming file \"${file_name}\" to \"${VORTEX_DB_DIR}/${VORTEX_DB_FILE}\"."
[ "${VORTEX_DEBUG-}" = "1" ] && note "Moving from: ${file_name}"
[ "${VORTEX_DEBUG-}" = "1" ] && note "Moving to: ${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"
mv "${file_name}" "${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"
mv_result=$?
[ "${mv_result}" -ne 0 ] && fail "Unable to rename file from '${file_name}' to '${VORTEX_DB_DIR}/${VORTEX_DB_FILE}'. mv exit code: ${mv_result}" && exit 1
[ "${VORTEX_DEBUG-}" = "1" ] && note "File rename completed successfully"

pass "Finished database dump download from Acquia."
