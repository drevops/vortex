#!/usr/bin/env bash
##
# Download DB dump from the latest Acquia Cloud backup.
#
# This script will discover latest available backup in the specified
# Acquia Cloud environment using Acquia Cloud API 2.0, download and decompress
# it into specified directory.
#
# It does not rely on 'drush ac-api-*' commands, which makes it capable of
# running on hosts without configured drush and drush aliases.
#
# It does require to have Cloud API token Key and Secret provided.
# To create your Cloud API token Acquia UI, go to
# Acquia Cloud UI -> Account -> API tokens -> Create Token
#
# Populate $AC_API_KEY and $AC_API_SECRET environment variables in .env.local
# file with values generated in the step above.
#
# @see https://docs.acquia.com/acquia-cloud/develop/api/auth/#cloud-generate-api-token
# @see https://cloudapi-docs.acquia.com/#/Environments/getEnvironmentsDatabaseDownloadBackup

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

#-------------------------------------------------------------------------------
#                             REQUIRED VARIABLES
#-------------------------------------------------------------------------------

# Application name. Used to discover UUID.
AC_API_APP_NAME="${AC_API_APP_NAME:-}"
# Source environment name to download the database dump.
AC_API_DB_ENV="${AC_API_DB_ENV:-}"
# Database name within source environment to download the database dump.
AC_API_DB_NAME="${AC_API_DB_NAME:-}"

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

# Directory where DB dumps are stored.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

# Flag to decompress backup. If not set to 1 - the DB dump will only be
# downloaded and not decompressed.
DB_DECOMPRESS_BACKUP=${DB_DECOMPRESS_BACKUP:-1}

# Use symlink when downloading files.
DB_USE_SYMLINK="${DB_USE_SYMLINK:-true}"

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
[ -z "${AC_API_DB_ENV}" ] && echo "ERROR: Missing value for AC_API_DB_ENV." && exit 1
[ -z "${AC_API_DB_NAME}" ] && echo "ERROR: Missing value for AC_API_DB_NAME." && exit 1
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

echo "==> Retrieving ${AC_API_DB_ENV} environment id."
ENVS_JSON=$(curl -s -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "https://cloud.acquia.com/api/applications/${APP_UUID}/environments?filter=name%3D${AC_API_DB_ENV}")
ENV_ID=$(echo "${ENVS_JSON}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${ENV_ID}" ] && echo "ERROR: Unable to retrieve an environment ID." && exit 1

echo "==> Discovering latest backup id for DB ${AC_API_DB_NAME}."
BACKUPS_JSON=$(curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "https://cloud.acquia.com/api/environments/${ENV_ID}/databases/${AC_API_DB_NAME}/backups?sort=created")
# Acquia response has all backups sorted chronologically by created date.
BACKUP_ID=$(echo "${BACKUPS_JSON}" | extract_json_value "_embedded" | extract_json_value "items" | extract_json_last_value "id")
[ -z "${BACKUP_ID}" ] && echo "ERROR: Unable to discover backup id." && exit 1

# Insert backup id as a suffix.
db_dump_ext="${DB_FILE##*.}"
db_dump_file_actual_prefix="${AC_API_DB_NAME}_backup_"
db_dump_file_actual=${DB_DIR}/${db_dump_file_actual_prefix}${BACKUP_ID}.${db_dump_ext}
db_dump_discovered=${db_dump_file_actual}
db_dump_compressed=${db_dump_file_actual}.gz

if [ -f "${db_dump_discovered}" ] ; then
  echo "==> Found existing cached DB file \"${db_dump_discovered}\" for DB \"${AC_API_DB_NAME}\"."
else
  # If the gzipped version exists, then we don't need to re-download it.
  if [ ! -f "${db_dump_compressed}" ] ; then
    [ ! -d "${DB_DIR}" ] && echo "==> Creating dump directory ${DB_DIR}" && mkdir -p "${DB_DIR}"
    echo "==> Using latest backup id ${BACKUP_ID} for DB ${AC_API_DB_NAME}."

    echo "==> Discovering backup url."
    BACKUP_JSON=$(curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "https://cloud.acquia.com/api/environments/${ENV_ID}/databases/${AC_API_DB_NAME}/backups/${BACKUP_ID}/actions/download")
    BACKUP_URL=$(echo "${BACKUP_JSON}" | extract_json_value "url")
    [ -z "${BACKUP_URL}" ] && echo "ERROR: Unable to discover backup URL." && exit 1

    echo "==> Downloading DB dump into file ${db_dump_compressed}."
    curl --progress-bar -L -H 'Accept: application/json, version=2' -H "Authorization: Bearer $TOKEN" "${BACKUP_URL}" -o "${db_dump_compressed}"

    # shellcheck disable=SC2181
    [ $? -ne 0 ] && echo "ERROR: Unable to download database ${AC_API_DB_NAME}." && exit 1
  else
    echo "==> Found existing cached gzipped DB file ${db_dump_compressed} for DB ${AC_API_DB_NAME}."
  fi

  # Expanding file, if required.
  if [ "${DB_DECOMPRESS_BACKUP}" != "0" ] ; then
    echo "==> Expanding DB file ${db_dump_compressed} into ${db_dump_file_actual}."
    gunzip -c "${db_dump_compressed}" > "${db_dump_file_actual}"
    decompress_result=$?
    rm "${db_dump_compressed}"
    [ ! -f "${db_dump_file_actual}" ] || [ "${decompress_result}" != 0 ] && echo "ERROR: Unable to process DB dump file \"${db_dump_file_actual}\"." && rm -f "${db_dump_compressed}" && rm -f "${db_dump_file_actual}" && exit 1
  fi
fi

echo "==> Expanded file."
ls -Alh "${db_dump_file_actual}"

if [ "${DB_USE_SYMLINK}" == true ]; then
  latest_symlink="${DB_FILE}"
  if [ -f "${db_dump_file_actual}" ] ; then
    echo "==> Creating a symlink \"$(basename "${db_dump_file_actual}")\" => ${latest_symlink}."
    (cd "${DB_DIR}" && rm -f "${latest_symlink}" && ln -s "$(basename "${db_dump_file_actual}")" "${latest_symlink}")
  fi

  latest_symlink="${latest_symlink}.gz"
  if [ -f "${db_dump_compressed}" ] ; then
    echo "==> Creating a symlink \"$(basename "${db_dump_compressed}")\" => \"${latest_symlink}\"."
    (cd "${DB_DIR}" && rm -f "${latest_symlink}" && ln -s "$(basename "${db_dump_compressed}")" "${latest_symlink}")
  fi
else
  echo "==> Renaming file \"${db_dump_file_actual}\" to \"${DB_DIR}/${DB_FILE}\"."
  mv "${db_dump_file_actual}" "${DB_DIR}/${DB_FILE}"
fi
