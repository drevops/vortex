#!/usr/bin/env bash
# shellcheck disable=SC2086
##
# Export database.
#

set -e

# Path to directory to store the DB dump.
DB_DUMP_DIR="${DB_DUMP_DIR:-/app/.data}"

# Drush alias.
DRUSH_ALIAS="${DRUSH_ALIAS:-}"

# ------------------------------------------------------------------------------

# Create temporary directory to store database dump.
mkdir -p "${DB_DUMP_DIR}"

# Create dump file name with a timestamp or use the file name provided
# as a first argument.
DUMP_FILE=$([ "${1}" ] && echo "${DB_DUMP_DIR}/${1}" || echo "${DB_DUMP_DIR}/db_export_$(date +%Y_%m_%d_%H_%M_%S).sql")

# Dump database into a file.
drush ${DRUSH_ALIAS} sql-dump --skip-tables-key=common --result-file="${DUMP_FILE}" -q

# Check that file was saved and output saved dump file name.
if [ -f "${DUMP_FILE}" ] && [ -s "${DUMP_FILE}" ]; then
  echo "==> Exported database dump saved"
else
  echo "ERROR: Unable to save dump file" && exit 1
fi
