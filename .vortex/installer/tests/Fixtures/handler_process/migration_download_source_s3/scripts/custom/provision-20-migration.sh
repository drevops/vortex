#!/usr/bin/env bash
##
# Import migration database and run migrations.
#
# This script is called during site provisioning via provision.sh.
# Customize the migration names at the bottom of this file.
#
# shellcheck disable=SC2086

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

# Skip all migrations.
MIGRATION_SKIP="${MIGRATION_SKIP:-0}"

# Skip rollback of migrations before import.
MIGRATION_ROLLBACK_SKIP="${MIGRATION_ROLLBACK_SKIP:-1}"

# Limit the number of entities to import. Set to 'all' to import all.
MIGRATION_IMPORT_LIMIT="${MIGRATION_IMPORT_LIMIT:-50}"

# Update already imported entities during migration.
MIGRATION_UPDATE="${MIGRATION_UPDATE:-0}"

# Feedback frequency for migration progress.
MIGRATION_FEEDBACK="${MIGRATION_FEEDBACK:-50}"

# Import migration source database. Set to 1 to import, 0 to skip.
MIGRATION_SOURCE_DB_IMPORT="${MIGRATION_SOURCE_DB_IMPORT:-${VORTEX_PROVISION_OVERRIDE_DB:-0}}"

# Table name to probe in the source database to verify it is not corrupted.
MIGRATION_SOURCE_DB_PROBE_TABLE="${MIGRATION_SOURCE_DB_PROBE_TABLE:-categories}"

# Directory with database dump file.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Migration database dump file name.
VORTEX_DOWNLOAD_DB2_FILE="${VORTEX_DOWNLOAD_DB2_FILE:-db2.sql}"

# ------------------------------------------------------------------------------

info "Started migration operations."

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Skip migrations in production.
if [ "${environment}" = "prod" ]; then
  MIGRATION_SKIP=1
fi

note "Migration skip:          ${MIGRATION_SKIP}"
note "Migration limit:         ${MIGRATION_IMPORT_LIMIT}"
note "Migration skip rollback: ${MIGRATION_ROLLBACK_SKIP}"
note "Migration update:        ${MIGRATION_UPDATE}"
note "Migration feedback:      ${MIGRATION_FEEDBACK}"
note "Source DB import:        ${MIGRATION_SOURCE_DB_IMPORT}"
echo

if [ "${MIGRATION_SKIP}" = "1" ]; then
  info "Skipping migrations. MIGRATION_SKIP is set to 1."
  exit 0
fi

# Helper function to run a single migration with configured options.
run_migration() {
  local migration_name="${1}"
  shift

  drush migrate:reset-status "${migration_name}" || {
    fail "Failed to reset migration status for ${migration_name}."
    exit 1
  }

  task "Running migration: ${migration_name}"
  local opts=()

  opts+=("--feedback=${MIGRATION_FEEDBACK}")

  if printf '%s\n' "${MIGRATION_IMPORT_LIMIT}" | grep -q '^[0-9][0-9]*$' && [ "${MIGRATION_IMPORT_LIMIT}" -gt 0 ]; then
    opts+=("--limit=${MIGRATION_IMPORT_LIMIT}")
  fi

  if [ "${MIGRATION_UPDATE}" = "1" ]; then
    opts+=("--update")
  fi

  # Add any additional arguments passed to the function.
  opts+=("$@")

  drush migrate:import "${opts[@]}" "${migration_name}" || {
    drush migrate:messages "${migration_name}"
    exit 1
  }
}

# Detect if existing migration source database is corrupted.
if [ "${MIGRATION_SOURCE_DB_IMPORT}" != "1" ]; then
  note "Source database import is set to be skipped. Checking existing database."
  task "Probing for '${MIGRATION_SOURCE_DB_PROBE_TABLE}' table in the source database."
  if ! drush sql:query --database=migrate "SELECT COUNT(*) FROM ${MIGRATION_SOURCE_DB_PROBE_TABLE}" >/dev/null 2>&1; then
    note "Migration source database is corrupted or empty. Re-importing."
    MIGRATION_SOURCE_DB_IMPORT=1
  fi
fi

# Import the migration source database from the dump file.
if [ "${MIGRATION_SOURCE_DB_IMPORT}" = "1" ]; then
  task "Importing migration source database."

  [ ! -f "${VORTEX_DB_DIR}/${VORTEX_DOWNLOAD_DB2_FILE}" ] && fail "Migration source database file not found. Please run 'ahoy download-db2'." && exit 1

  drush sql:drop --database=migrate
  # shellcheck disable=SC2091
  $(drush sql:connect --database=migrate) <"${VORTEX_DB_DIR}/${VORTEX_DOWNLOAD_DB2_FILE}"

  pass "Imported migration source database."
else
  note "Using existing migration source database."
fi

task "Verifying migration source database."
if ! drush sql:query --database=migrate "SELECT COUNT(*) FROM ${MIGRATION_SOURCE_DB_PROBE_TABLE}" >/dev/null 2>&1; then
  fail "Migration source database is corrupted."
  drush sql:query --database=migrate "SHOW TABLES;"
  exit 1
fi

# Enable custom migration modules.
task "Enabling migration modules."
drush pm:install ys_migrate

task "Starting migrations."

if [ "${MIGRATION_ROLLBACK_SKIP}" = "1" ]; then
  note "Skipping rollback of all migrations."
else
  task "Rolling back all migrations."
  drush migrate:rollback --all || true
fi
echo

# -----------------------------------------------------------------------------
# Add your migrations below.
# -----------------------------------------------------------------------------

run_migration ys_migrate_categories

echo
note "Finished migrations."

drush migrate:status

info "Finished migration operations."
