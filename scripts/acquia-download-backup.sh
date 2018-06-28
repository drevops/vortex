#!/usr/bin/env bash
##
# Download DB dump from latest Acquia Cloud backup.
#
# This script will discover latest available backup in the specified
# Acquia Cloud environment, download and decompress it into specified directory.
#
# It does not rely on 'drush ac-*' command, which makes it capable of running
# on hosts without configured drush and drush aliases.
#
# @see https://cloudapi.acquia.com/#GET__sites__site_envs__env_dbs__db_backups__backup_download-instance_route

################################################################################
########################### REQUIRED VARIABLES #################################
################################################################################

# Acquia Cloud UI->Account->Credentials->Cloud API->E-mail
AC_API_USER_NAME=${AC_API_USER_NAME:-}
# Acquia Cloud UI->Account->Credentials->Cloud API->Private key
AC_API_USER_PASS=${AC_API_USER_PASS:-}
# 'prod:<git_repo_name>'
AC_API_DB_SITE=${AC_API_DB_SITE:-}
AC_API_DB_ENV=${AC_API_DB_ENV:-}
AC_API_DB_NAME=${AC_API_DB_NAME:-}

################################################################################
########################### OPTIONAL VARIABLES #################################
################################################################################

# Backup id. If not specified - latest backup id will be discovered and used.
AC_API_DB_BACKUP_ID=${AC_API_DB_BACKUP_ID:-}

# DB dump directory.
DB_DUMP_DIR=${DB_DUMP_DIR:-.data}

# Resulting DB dump file name. Used by external scripts to import DB.
# Note that absolute path will be $PROJECT_PATH/$DB_DUMP_DIR/$DB_DUMP_FILE_NAME
DB_DUMP_FILE_NAME=${DB_DUMP_FILE_NAME:-db.sql}

# Absolute path to resulting file, including name. May be used to override
# resulting dump path if it is located outside of current project.
DB_DUMP_FILE=${DB_DUMP_FILE:-}

# Flag to decompress backup.
DECOMPRESS_BACKUP=${DECOMPRESS_BACKUP:-1}

# Flag to remove old cached dumps.
REMOVE_CACHED_DUMPS=${REMOVE_CACHED_DUMPS:-0}

################################################################################
#################### DO NOT CHANGE ANYTHING BELOW THIS LINE ####################
################################################################################
SELF_START_TIME=$(date +%s)
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Find absolute script path.
SELF_DIR=$(dirname -- ${BASH_SOURCE[0]})
SELF_PATH=$(cd -P -- "$SELF_DIR" && pwd -P)/$(basename -- ${BASH_SOURCE[0]})

# Find absolute project root.
PROJECT_PATH=$(dirname $(dirname $SELF_PATH))

# Expand DB dump file name to absolute path.
DB_DUMP_FILE=${DB_DUMP_FILE:-$PROJECT_PATH/${DB_DUMP_DIR}/$DB_DUMP_FILE_NAME}
# Set DB dump dir to absolute path.
DB_DUMP_DIR=$(dirname $DB_DUMP_FILE)

# Pre-flight checks.
which curl > /dev/null ||  {
  echo "==> ERROR: curl is not available in this session" && exit 1
}

[ "$AC_API_USER_NAME" == "" ] && echo "==> ERROR: Missing value for \$AC_API_USER_NAME" && exit 1
[ "$AC_API_USER_PASS" == "" ] && echo "==> ERROR: Missing value for \$AC_API_USER_PASS" && exit 1
[ "$AC_API_DB_SITE" == "" ] && echo "==> ERROR: Missing value for \$AC_API_DB_SITE" && exit 1
[ "$AC_API_DB_ENV" == "" ] && echo "==> ERROR: Missing value for \$AC_API_DB_ENV" && exit 1
[ "$AC_API_DB_NAME" == "" ] && echo "==> ERROR: Missing value for \$AC_API_DB_NAME" && exit 1

# @todo: add pre-flight checks for variable formats.

# Function to extract last value from JSON object passed via STDIN.
extract_json_last_value() {
  local key=$1
  php -r '$data=json_decode(file_get_contents("php://stdin"), TRUE); $last=array_pop($data); isset($last["'$key'"]) ? print $last["'$key'"] : exit(1);'
}

LATEST_BACKUP=0
if [ "$AC_API_DB_BACKUP_ID" == "" ] ; then
  echo "==> Discovering latest backup id for DB $AC_API_DB_NAME"
  echo curl --progress-bar -L -u $AC_API_USER_NAME:$AC_API_USER_PASS https://cloudapi.acquia.com/v1/sites/$AC_API_DB_SITE/envs/$AC_API_DB_ENV/dbs/$AC_API_DB_NAME/backups.json
  BACKUPS_JSON=$(curl --progress-bar -L -u $AC_API_USER_NAME:$AC_API_USER_PASS https://cloudapi.acquia.com/v1/sites/$AC_API_DB_SITE/envs/$AC_API_DB_ENV/dbs/$AC_API_DB_NAME/backups.json)
  # Acquia response has all backups sorted chronologically by created date.
  AC_API_DB_BACKUP_ID=$(echo $BACKUPS_JSON | extract_json_last_value "id")
  [ "$AC_API_DB_BACKUP_ID" == "" ] && echo "==> ERROR: Unable to discover backup id" && exit 1
  LATEST_BACKUP=1
fi

# Insert backup id as a suffix.
DB_DUMP_EXT="${DB_DUMP_FILE##*.}"
DB_DUMP_FILE_ACTUAL_PREFIX="${AC_API_DB_NAME}_backup_"
DB_DUMP_FILE_ACTUAL=${DB_DUMP_DIR}/${DB_DUMP_FILE_ACTUAL_PREFIX}${AC_API_DB_BACKUP_ID}.${DB_DUMP_EXT}
DB_DUMP_DISCOVERED=$DB_DUMP_FILE_ACTUAL
DB_DUMP_COMPRESSED=$DB_DUMP_FILE_ACTUAL.gz

if [ -f $DB_DUMP_DISCOVERED ] ; then
  echo "==> Found existing cached DB file $DB_DUMP_DISCOVERED for DB $AC_API_DB_NAME"
else
  # If the gzip version exists, then we don't need to re-download it.
  if [ ! -f $DB_DUMP_COMPRESSED ] ; then
    [ ! -d $DB_DUMP_DIR ] && echo "==> Creating dump directory $DB_DUMP_DIR" && mkdir -p $DB_DUMP_DIR
    [ "$REMOVE_CACHED_DUMPS" == "1" ] && echo "==> Removing all previously cached DB dumps" && rm -Rf $DB_DUMP_DIR/${DB_DUMP_FILE_ACTUAL_PREFIX}*
    echo "==> Using latest backup id $AC_API_DB_BACKUP_ID for DB $AC_API_DB_NAME"
    echo "==> Downloading DB dump into file $DB_DUMP_COMPRESSED"
    curl --progress-bar -L -u $AC_API_USER_NAME:$AC_API_USER_PASS https://cloudapi.acquia.com/v1/sites/$AC_API_DB_SITE/envs/$AC_API_DB_ENV/dbs/$AC_API_DB_NAME/backups/$AC_API_DB_BACKUP_ID/download.json -o $DB_DUMP_COMPRESSED
  else
    echo "==> Found existing cached gzipped DB file $DB_DUMP_COMPRESSED for DB $AC_API_DB_NAME"
  fi
  # Expanding file, if required.
  if [ $DECOMPRESS_BACKUP != 0 ] ; then
    echo "==> Expanding DB file $DB_DUMP_COMPRESSED into $DB_DUMP_FILE_ACTUAL"
    gunzip -c $DB_DUMP_COMPRESSED > $DB_DUMP_FILE_ACTUAL
    DECOMPRESS_RESULT=$?
    rm $DB_DUMP_COMPRESSED
    [ ! -f $DB_DUMP_FILE_ACTUAL ] || [ $DECOMPRESS_RESULT != 0 ] && echo "==> ERROR: Unable to process DB dump file $DB_DUMP_FILE_ACTUAL" && rm -f $DB_DUMP_COMPRESSED && rm -f $DB_DUMP_FILE_ACTUAL && exit 1
  fi
fi

# Create a symlink to the latest backup.
if [ "$LATEST_BACKUP" != "0" ] ; then
  LATEST_SYMLINK=$DB_DUMP_FILE
  if [ -f $DB_DUMP_FILE_ACTUAL ] ; then
    echo "==> Creating symlink $(basename $DB_DUMP_FILE_ACTUAL) => $LATEST_SYMLINK"
    (cd $DB_DUMP_DIR && rm -f $LATEST_SYMLINK && ln -s $(basename $DB_DUMP_FILE_ACTUAL) $LATEST_SYMLINK)
  fi
  LATEST_SYMLINK=$LATEST_SYMLINK.gz
  if [ -f $DB_DUMP_COMPRESSED ] ; then
    echo "==> Creating symlink $(basename $DB_DUMP_COMPRESSED) => $LATEST_SYMLINK"
    (cd $DB_DUMP_DIR && rm -f $LATEST_SYMLINK && ln -s $(basename $DB_DUMP_COMPRESSED) $LATEST_SYMLINK)
  fi
fi

SECONDS=$(date +%s)
SELF_ELAPSED_TIME=$(($SECONDS - $SELF_START_TIME))
echo "==> Build duration: $(($SELF_ELAPSED_TIME/60)) min $(($SELF_ELAPSED_TIME%60)) sec"
