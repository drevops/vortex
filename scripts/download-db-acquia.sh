#!/usr/bin/env bash
##
# Download DB dump from the latest Acquia Cloud backup.
#
# This script will discover latest available backup in the specified
# Acquia Cloud environment using Acquia Cloud API 1.0, download and decompress
# it into specified directory.
#
# It does not rely on 'drush ac-*' command, which makes it capable of running
# on hosts without configured drush and drush aliases.
#
# @see https://cloudapi.acquia.com/#GET__sites__site_envs__env_dbs__db_backups__backup_download-instance_route

#-------------------------------------------------------------------------------
#                             REQUIRED VARIABLES
#-------------------------------------------------------------------------------

# Acquia Cloud UI -> Account -> Credentials -> Cloud API -> E-mail
AC_API_USER_NAME=${AC_API_USER_NAME:-}
# Acquia Cloud UI -> Account -> Credentials -> Cloud API -> Private key
AC_API_USER_PASS=${AC_API_USER_PASS:-}
# 'prod:<git_repo_name>'
AC_API_DB_SITE=${AC_API_DB_SITE:-}
AC_API_DB_ENV=${AC_API_DB_ENV:-}
AC_API_DB_NAME=${AC_API_DB_NAME:-}

#-------------------------------------------------------------------------------
#                              OPTIONAL VARIABLES
#-------------------------------------------------------------------------------

# Backup id. If not specified - the latest backup id will be discovered and used.
AC_API_DB_BACKUP_ID=${AC_API_DB_BACKUP_ID:-}

# Location of the Acquia Cloud API credentials file after running 'drush ac-api-login'.
AC_CREDENTIALS_FILE=${AC_CREDENTIALS_FILE:-$HOME/.acquia/cloudapi.conf}

# Directory where DB dumps are stored.
DATADIR=${DATADIR:-.data}

# Resulting DB dump file name. Used by external scripts to import DB.
# Note that absolute path will be ${project_path}/${DATADIR}/${DB_FILE_NAME}
DB_FILE_NAME=${DB_FILE_NAME:-db.sql}

# Absolute path to resulting file, including name. May be used to override
# resulting dump path if it is located outside of the current project.
DB_FILE=${DB_FILE:-}

# Flag to decompress backup.
DB_DECOMPRESS_BACKUP=${DB_DECOMPRESS_BACKUP:-1}

# Flag to remove old cached dumps.
DB_REMOVE_CACHED_DUMPS=${DB_REMOVE_CACHED_DUMPS:-0}

# Internal flag to proceed with the download.
DB_DOWNLOAD_PROCEED=${DB_DOWNLOAD_PROCEED:-1}

#-------------------------------------------------------------------------------
#                       DO NOT CHANGE ANYTHING BELOW THIS LINE
#-------------------------------------------------------------------------------

#
# Function to extract last value from JSON object passed via STDIN.
#
extract_json_last_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print \$last[\"${key}\"] : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print \$data[\"${key}\"] : exit(1);"
}

self_start_time=$(date +%s)

# Find absolute script path.
self_dir=$(dirname -- "${BASH_SOURCE[0]}")
self_path=$(cd -P -- "${self_dir}" && pwd -P)/$(basename -- "${BASH_SOURCE[0]}")

# Find absolute project root.
project_path=$(dirname "$(dirname "${self_path}")")

# Expand DB dump file name into absolute path.
DB_FILE=${DB_FILE:-${project_path}/${DATADIR}/${DB_FILE_NAME}}
# Set DB dump dir to an absolute path.
DATADIR=$(dirname "${DB_FILE}")

# Pre-flight checks.
command -v curl > /dev/null ||  {
  echo "==> ERROR: curl is not available in this session" && exit 1
}

# Try to read credentials from the stored config file after `drush ac-api-login`.
if [ "${AC_API_USER_NAME}" == "" ] && [ -f "${AC_CREDENTIALS_FILE}" ]; then
  AC_API_USER_NAME=$(extract_json_value "mail" < "${AC_CREDENTIALS_FILE}")
  AC_API_USER_PASS=$(extract_json_value "key" < "${AC_CREDENTIALS_FILE}")
fi

# Check that all required variables are present.
[ "${AC_API_USER_NAME}" == "" ] && echo "==> ERROR: Missing value for \${AC_API_USER_NAME}" && exit 1
[ "${AC_API_USER_PASS}" == "" ] && echo "==> ERROR: Missing value for \${AC_API_USER_PASS}" && exit 1
[ "${AC_API_DB_SITE}" == "" ] && echo "==> ERROR: Missing value for \${AC_API_DB_SITE}" && exit 1
[ "${AC_API_DB_ENV}" == "" ] && echo "==> ERROR: Missing value for \${AC_API_DB_ENV}" && exit 1
[ "${AC_API_DB_NAME}" == "" ] && echo "==> ERROR: Missing value for \${AC_API_DB_NAME}" && exit 1

# Kill-switch to proceed with download.
[ "${DB_DOWNLOAD_PROCEED}" -ne 1 ] && echo "Skipping Acquia database download" && exit 0

latest_backup=0
if [ "${AC_API_DB_BACKUP_ID}" == "" ] ; then
  echo "==> Discovering latest backup id for DB ${AC_API_DB_NAME}"
  echo curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups.json"
  BACKUPS_JSON=$(curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups.json")
  # Acquia response has all backups sorted chronologically by created date.
  AC_API_DB_BACKUP_ID=$(echo "${BACKUPS_JSON}" | extract_json_last_value "id")
  [ "${AC_API_DB_BACKUP_ID}" == "" ] && echo "==> ERROR: Unable to discover backup id" && exit 1
  latest_backup=1
fi

# Insert backup id as a suffix.
db_dump_ext="${DB_FILE##*.}"
db_dump_file_actual_prefix="${AC_API_DB_NAME}_backup_"
db_dump_file_actual=${DATADIR}/${db_dump_file_actual_prefix}${AC_API_DB_BACKUP_ID}.${db_dump_ext}
db_dump_discovered=${db_dump_file_actual}
db_dump_compressed=${db_dump_file_actual}.gz

if [ -f "${db_dump_discovered}" ] ; then
  echo "==> Found existing cached DB file \"${db_dump_discovered}\" for DB \"${AC_API_DB_NAME}\""
else
  # If the gzipped version exists, then we don't need to re-download it.
  if [ ! -f "${db_dump_compressed}" ] ; then
    [ ! -d "${DATADIR}" ] && echo "==> Creating dump directory ${DATADIR}" && mkdir -p "${DATADIR}"
    [ "${DB_REMOVE_CACHED_DUMPS}" == "1" ] && echo "==> Removing all previously cached DB dumps" && rm -Rf "${DATADIR}/${db_dump_file_actual_prefix:?}*"
    echo "==> Using latest backup id ${AC_API_DB_BACKUP_ID} for DB ${AC_API_DB_NAME}"
    echo "==> Downloading DB dump into file ${db_dump_compressed}"
    echo curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups/${AC_API_DB_BACKUP_ID}/download.json" -o "${db_dump_compressed}"
    curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups/${AC_API_DB_BACKUP_ID}/download.json" -o "${db_dump_compressed}"
    # shellcheck disable=SC2181
    [ $? -ne 0 ] && echo "==> ERROR: Unable to download database ${AC_API_DB_NAME}" && exit
  else
    echo "==> Found existing cached gzipped DB file ${db_dump_compressed} for DB ${AC_API_DB_NAME}"
  fi

  # Expanding file, if required.
  if [ "${DB_DECOMPRESS_BACKUP}" != "0" ] ; then
    echo "==> Expanding DB file ${db_dump_compressed} into ${db_dump_file_actual}"
    gunzip -c "${db_dump_compressed}" > "${db_dump_file_actual}"
    decompress_result=$?
    rm "${db_dump_compressed}"
    [ ! -f "${db_dump_file_actual}" ] || [ "${decompress_result}" != 0 ] && echo "==> ERROR: Unable to process DB dump file \"${db_dump_file_actual}\"" && rm -f "${db_dump_compressed}" && rm -f "${db_dump_file_actual}" && exit 1
  fi
fi

# Create a symlink to the latest backup.
if [ "${latest_backup}" != "0" ] ; then
  latest_symlink=${DB_FILE}
  if [ -f "${db_dump_file_actual}" ] ; then
    echo "==> Creating a symlink \"$(basename "${db_dump_file_actual}")\" => ${latest_symlink}"
    (cd "${DATADIR}" && rm -f "${latest_symlink}" && ln -s "$(basename "${db_dump_file_actual}")" "${latest_symlink}")
  fi

  latest_symlink=${latest_symlink}.gz
  if [ -f "${db_dump_compressed}" ] ; then
    echo "==> Creating a symlink \"$(basename "${db_dump_compressed}")\" => \"${latest_symlink}\""
    (cd "${DATADIR}" && rm -f "${latest_symlink}" && ln -s "$(basename "${db_dump_compressed}")" "${latest_symlink}")
  fi
fi

SECONDS=$(date +%s)
SELF_ELAPSED_TIME=$((SECONDS - self_start_time))
echo "==> Build duration: $((SELF_ELAPSED_TIME/60)) min $((SELF_ELAPSED_TIME%60)) sec"
