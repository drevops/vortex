#!/usr/bin/env bash
##
# Download DB dump from the latest Acquia Cloud backup.
#
# This script will discover latest available backup in the specified
# Acquia Cloud environment using Acquia Cloud API 1.0, download and decompress
# it into specified directory.
#
# It does not rely on 'drush ac-api-*' commands, which makes it capable of
# running on hosts without configured drush and drush aliases.
#
# It does however supports reading credentials from Acquia cloud config file
# usually located in ${HOME}/.acquia/cloudapi.json. To retrieve your Cloud API
# credentials from Acquia UI, go to
# Acquia Cloud UI -> Account -> Credentials -> Cloud API -> E-mail
# Acquia Cloud UI -> Account -> Credentials -> Cloud API -> Private key->Show
#
# Once retrieved, run `drush ac-api-login` in the environment where the database
# will be downloaded and provide email and token.
# Alternatively, populate $AC_API_USER_NAME and $AC_API_USER_PASS environment
# variables.
#
# @see https://cloudapi.acquia.com/#GET__sites__site_envs__env_dbs__db_backups__backup_download-instance_route

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

#-------------------------------------------------------------------------------
#                             REQUIRED VARIABLES
#-------------------------------------------------------------------------------

# 'prod:<git_repo_name>'
AC_API_DB_SITE="${AC_API_DB_SITE:-}"
AC_API_DB_ENV="${AC_API_DB_ENV:-}"
AC_API_DB_NAME="${AC_API_DB_NAME:-}"

#-------------------------------------------------------------------------------
#                              OPTIONAL VARIABLES
#-------------------------------------------------------------------------------

# Both user name and password are read from Acquia Cloud API config file by
# default and should be provided through variables only in environments that do
# not have Acquia CLoud API config file created (usually, non-local environments).
AC_API_USER_NAME="${AC_API_USER_NAME:-}"
AC_API_USER_PASS="${AC_API_USER_PASS:-}"

# Backup id. If not specified - the latest backup id will be discovered and used.
AC_API_DB_BACKUP_ID=${AC_API_DB_BACKUP_ID:-}

# Location of the Acquia Cloud API credentials file after running 'drush ac-api-login'.
AC_CREDENTIALS_FILE=${AC_CREDENTIALS_FILE:-${HOME}/.acquia/cloudapi.conf}

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
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$last=array_pop(\$data); isset(\$last[\"${key}\"]) ? print \$last[\"${key}\"] : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print \$data[\"${key}\"] : exit(1);"
}

# Pre-flight checks.
command -v curl > /dev/null || ( echo "ERROR: curl command is not available." && exit 1 )

# Try to read credentials from the stored config file after running `drush ac-api-login`.
if [ -z "${AC_API_USER_NAME}" ] && [ -f "${AC_CREDENTIALS_FILE}" ]; then
  set +e
  AC_API_USER_NAME=$(extract_json_value "mail" < "${AC_CREDENTIALS_FILE}")
  [ -z "${AC_API_USER_NAME}" ] && AC_API_USER_NAME=$(extract_json_value "email" < "${AC_CREDENTIALS_FILE}")
  AC_API_USER_PASS=$(extract_json_value "key" < "${AC_CREDENTIALS_FILE}")
  set -e
fi

# Check that all required variables are present.
[ -z "${AC_API_USER_NAME}" ] && echo "ERROR: Missing value for AC_API_USER_NAME." && exit 1
[ -z "${AC_API_USER_PASS}" ] && echo "ERROR: Missing value for AC_API_USER_PASS." && exit 1
[ -z "${AC_API_DB_SITE}" ] && echo "ERROR: Missing value for AC_API_DB_SITE." && exit 1
[ -z "${AC_API_DB_ENV}" ] && echo "ERROR: Missing value for AC_API_DB_ENV." && exit 1
[ -z "${AC_API_DB_NAME}" ] && echo "ERROR: Missing value for AC_API_DB_NAME." && exit 1

latest_backup=0
if [ -z "${AC_API_DB_BACKUP_ID}" ] ; then
  echo "==> Discovering latest backup id for DB ${AC_API_DB_NAME}."
  BACKUPS_JSON=$(curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups.json")
  # Acquia response has all backups sorted chronologically by created date.
  AC_API_DB_BACKUP_ID=$(echo "${BACKUPS_JSON}" | extract_json_last_value "id")
  [ -z "${AC_API_DB_BACKUP_ID}" ] && echo "ERROR: Unable to discover backup id." && exit 1
  latest_backup=1
fi

# Insert backup id as a suffix.
db_dump_ext="${DB_FILE##*.}"
db_dump_file_actual_prefix="${AC_API_DB_NAME}_backup_"
db_dump_file_actual=${DB_DIR}/${db_dump_file_actual_prefix}${AC_API_DB_BACKUP_ID}.${db_dump_ext}
db_dump_discovered=${db_dump_file_actual}
db_dump_compressed=${db_dump_file_actual}.gz

if [ -f "${db_dump_discovered}" ] ; then
  echo "==> Found existing cached DB file \"${db_dump_discovered}\" for DB \"${AC_API_DB_NAME}\"."
else
  # If the gzipped version exists, then we don't need to re-download it.
  if [ ! -f "${db_dump_compressed}" ] ; then
    [ ! -d "${DB_DIR}" ] && echo "==> Creating dump directory ${DB_DIR}" && mkdir -p "${DB_DIR}"
    echo "==> Using latest backup id ${AC_API_DB_BACKUP_ID} for DB ${AC_API_DB_NAME}."
    echo "==> Downloading DB dump into file ${db_dump_compressed}."
    curl --progress-bar -L -u "${AC_API_USER_NAME}":"${AC_API_USER_PASS}" "https://cloudapi.acquia.com/v1/sites/${AC_API_DB_SITE}/envs/${AC_API_DB_ENV}/dbs/${AC_API_DB_NAME}/backups/${AC_API_DB_BACKUP_ID}/download.json" -o "${db_dump_compressed}"
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
ls -alh "${db_dump_file_actual}"

if [ "${DB_USE_SYMLINK}" == true ]; then
  # Create a symlink to the latest backup.
  if [ "${latest_backup}" != "0" ] ; then
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
  fi
else
  echo "==> Renaming file \"${db_dump_file_actual}\" to \"${DB_DIR}/${DB_FILE}\"."
  mv "${db_dump_file_actual}" "${DB_DIR}/${DB_FILE}"
fi
