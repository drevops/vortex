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

# Skip all migrations.
DRUPAL_MIGRATION_SKIP="${DRUPAL_MIGRATION_SKIP:-0}"

# Skip rollback of migrations before import.
DRUPAL_MIGRATION_ROLLBACK_SKIP="${DRUPAL_MIGRATION_ROLLBACK_SKIP:-1}"

# Limit the number of entities to import. Set to 'all' to import all.
DRUPAL_MIGRATION_IMPORT_LIMIT="${DRUPAL_MIGRATION_IMPORT_LIMIT:-50}"

# Update already imported entities during migration.
DRUPAL_MIGRATION_UPDATE="${DRUPAL_MIGRATION_UPDATE:-0}"

# Feedback frequency for migration progress.
DRUPAL_MIGRATION_FEEDBACK="${DRUPAL_MIGRATION_FEEDBACK:-50}"

# Import migration source database. Set to 1 to import, 0 to skip.
DRUPAL_MIGRATION_SOURCE_DB_IMPORT="${DRUPAL_MIGRATION_SOURCE_DB_IMPORT:-${VORTEX_PROVISION_OVERRIDE_DB:-0}}"

# Table name to probe in the source database to verify it is not corrupted.
DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE="${DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE:-categories}"

# Directory with database dump file.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Migration database dump file name.
VORTEX_FETCH_DB2_FILE="${VORTEX_FETCH_DB2_FILE:-db2.sql}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     < %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started migration operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Skip migrations in production.
if [ "${environment}" = "prod" ]; then
  DRUPAL_MIGRATION_SKIP=1
fi

note "Migration skip:          ${DRUPAL_MIGRATION_SKIP}"
note "Migration limit:         ${DRUPAL_MIGRATION_IMPORT_LIMIT}"
note "Migration skip rollback: ${DRUPAL_MIGRATION_ROLLBACK_SKIP}"
note "Migration update:        ${DRUPAL_MIGRATION_UPDATE}"
note "Migration feedback:      ${DRUPAL_MIGRATION_FEEDBACK}"
note "Source DB import:        ${DRUPAL_MIGRATION_SOURCE_DB_IMPORT}"
echo

if [ "${DRUPAL_MIGRATION_SKIP}" = "1" ]; then
  info "Skipped migrations. DRUPAL_MIGRATION_SKIP is set to 1."
  exit 0
fi

# Helper function to run a single migration with configured options.
run_migration() {
  local migration_name="${1}"
  shift

  drush migrate:reset-status "${migration_name}" || fail "Failed to reset migration status for ${migration_name}."

  task "Running migration: ${migration_name}."
  local opts=()

  opts+=("--feedback=${DRUPAL_MIGRATION_FEEDBACK}")

  if printf '%s\n' "${DRUPAL_MIGRATION_IMPORT_LIMIT}" | grep -q '^[0-9][0-9]*$' && [ "${DRUPAL_MIGRATION_IMPORT_LIMIT}" -gt 0 ]; then
    opts+=("--limit=${DRUPAL_MIGRATION_IMPORT_LIMIT}")
  fi

  if [ "${DRUPAL_MIGRATION_UPDATE}" = "1" ]; then
    opts+=("--update")
  fi

  # Add any additional arguments passed to the function.
  opts+=("$@")

  drush migrate:import "${opts[@]}" "${migration_name}" || {
    drush migrate:messages "${migration_name}"
    fail "Failed to run migration ${migration_name}."
  }

  pass "Migrated: ${migration_name}."
}

# Machine names of the indexes disabled for the duration of the migration.
search_indexes=""

disable_search_indexes() {
  search_indexes="$(drush php:eval "print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE])));")"

  [ -z "${search_indexes}" ] && return 0

  task "Disabling search indexes."
  for index in ${search_indexes}; do
    drush search-api:disable "${index}" || return 1
  done
  pass "Disabled search indexes."
}

# Only the indexes disabled above are enabled again, so an index that was
# already disabled before the migration stays that way.
enable_search_indexes() {
  [ -z "${search_indexes}" ] && return 0

  task "Enabling search indexes."
  # 'set -e' does not apply while the function runs as a condition, so the
  # failure is returned explicitly.
  for index in ${search_indexes}; do
    drush search-api:enable "${index}" || return 1
  done
  pass "Enabled search indexes."
}

# Preserves the exit status that triggered the restore, so a failed restore
# reports itself without replacing the original failure.
restore_search_indexes() {
  local status="${?}"

  trap - EXIT
  enable_search_indexes || note "Failed to enable search indexes."

  exit "${status}"
}

# Detect if existing migration source database is corrupted.
if [ "${DRUPAL_MIGRATION_SOURCE_DB_IMPORT}" != "1" ]; then
  note "Source database import is set to be skipped. Checking existing database."
  task "Probing for '${DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE}' table in the source database."
  if drush sql:query --database=migrate "SELECT COUNT(*) FROM ${DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE}" >/dev/null 2>&1; then
    pass "Source database is intact."
  else
    note "Migration source database is corrupted or empty. Re-importing."
    DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1
  fi
fi

# Import the migration source database from the dump file.
if [ "${DRUPAL_MIGRATION_SOURCE_DB_IMPORT}" = "1" ]; then
  task "Importing migration source database."

  [ ! -f "${VORTEX_DB_DIR}/${VORTEX_FETCH_DB2_FILE}" ] && fail "Migration source database file not found. Please run 'ahoy fetch-db2'."

  drush sql:drop --database=migrate
  # shellcheck disable=SC2091
  $(drush sql:connect --database=migrate) <"${VORTEX_DB_DIR}/${VORTEX_FETCH_DB2_FILE}"

  pass "Imported migration source database."
else
  note "Using existing migration source database."
fi

task "Verifying migration source database."
if ! drush sql:query --database=migrate "SELECT COUNT(*) FROM ${DRUPAL_MIGRATION_SOURCE_DB_PROBE_TABLE}" >/dev/null 2>&1; then
  drush sql:query --database=migrate "SHOW TABLES;"
  fail "Migration source database is corrupted."
fi
pass "Verified migration source database."

# Enable custom migration modules.
task "Enabling migration modules."
drush pm:install ys_migrate
pass "Enabled migration modules."

info "Starting migrations."

# Indexing every migrated entity on save is discarded work: the search indexing
# provision script rebuilds the index straight after this one.
disable_search_indexes || fail "Failed to disable search indexes."
# Restore the indexes even when a migration fails part-way through.
trap 'restore_search_indexes' EXIT

if [ "${DRUPAL_MIGRATION_ROLLBACK_SKIP}" = "1" ]; then
  note "Skipped rollback of all migrations."
else
  task "Rolling back all migrations."
  drush migrate:rollback --all || true
  pass "Rolled back all migrations."
fi
echo

# ------------------------------------------------------------------------------
# Add your migrations below.
# ------------------------------------------------------------------------------

run_migration ys_migrate_categories

echo
note "Finished migrations."

trap - EXIT
enable_search_indexes || fail "Failed to enable search indexes."

drush migrate:status

info "Finished migration operations."
