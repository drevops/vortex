#!/usr/bin/env bash
# shellcheck disable=SC2086
##
# Export database as a file.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Directory with database dump file.
DREVOPS_DB_EXPORT_FILE_DIR="${DREVOPS_DB_DIR:-${DREVOPS_DB_DIR}}"

# ------------------------------------------------------------------------------

echo "==> Started database file export."

# Use local or global Drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Create directory to store database dump.
mkdir -p "${DREVOPS_DB_EXPORT_FILE_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument.
dump_file=$([ "${1}" ] && echo "${DREVOPS_DB_EXPORT_FILE_DIR}/${1}" || echo "${DREVOPS_DB_EXPORT_FILE_DIR}/export_db_$(date +%Y_%m_%d_%H_%M_%S).sql")

# Dump database into a file. Also, convert relative path to an absolute one, as
# the result file is relative to Drupal root, but provided paths are relative
# to the project root.
$drush sql-dump --skip-tables-key=common --extra-dump=--no-tablespaces --result-file="${dump_file/.\//${DREVOPS_APP}/}" -q

# Check that file was saved and output saved dump file name.
if [ -f "${dump_file}" ] && [ -s "${dump_file}" ]; then
  echo "  > Exported database dump saved ${dump_file}."
else
  echo "ERROR: Unable to save dump file ${dump_file}." && exit 1
fi

echo "==> Finished database file export."
