#!/usr/bin/env bash
# shellcheck disable=SC2086
##
# Export database.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Drush alias.
DRUSH_ALIAS="${DRUSH_ALIAS:-}"

# ------------------------------------------------------------------------------

# Use local or global Drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

# Create directory to store database dump.
mkdir -p "${DB_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument.
DUMP_FILE=$([ "${1}" ] && echo "${DB_DIR}/${1}" || echo "${DB_DIR}/export_db_$(date +%Y_%m_%d_%H_%M_%S).sql")

# Dump database into a file. Also, convert relative path to an absolute one, as
# the result file is relative to Drupal root, but provided paths are relative
# to the project root.
$drush ${DRUSH_ALIAS} sql-dump --skip-tables-key=common --extra-dump=--no-tablespaces --result-file="${DUMP_FILE/.\//${APP}/}" -q

# Check that file was saved and output saved dump file name.
if [ -f "${DUMP_FILE}" ] && [ -s "${DUMP_FILE}" ]; then
  echo "==> Exported database dump saved ${DUMP_FILE}."
else
  echo "ERROR: Unable to save dump file ${DUMP_FILE}." && exit 1
fi
