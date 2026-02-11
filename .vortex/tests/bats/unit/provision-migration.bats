#!/usr/bin/env bats
##
# Unit tests for provision-20-migration.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision migration: default flow with import" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Import: drop and connect.
    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"

    # Verification after import.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Migration: reset, import, status.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y migrate:status"

    # Expected output.
    "Started migration operations."
    "Environment: local"
    "Migration skip:          0"
    "Migration limit:         50"
    "Source DB import:        1"
    "Importing migration source database."
    "Imported migration source database."
    "Verifying migration source database."
    "Enabling migration modules."
    "Starting migrations."
    "Skipping rollback of all migrations."
    "Running migration: ys_migrate_categories"
    "Finished migrations."
    "Finished migration operations."

    # Not expected.
    "- Skipping migrations. MIGRATION_SKIP is set to 1."
    "- Using existing migration source database."
    "- Migration source database is corrupted."
    "- Rolling back all migrations."
    "- Migration source database file not found."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: skip all migrations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SKIP=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    "Started migration operations."
    "Migration skip:          1"
    "Skipping migrations. MIGRATION_SKIP is set to 1."

    "- Importing migration source database."
    "- Starting migrations."
    "- Running migration:"
    "- Finished migrations."
    "- Finished migration operations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: production environment auto-skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # prod"

    "Started migration operations."
    "Environment: prod"
    "Migration skip:          1"
    "Skipping migrations. MIGRATION_SKIP is set to 1."

    "- Importing migration source database."
    "- Starting migrations."
    "- Finished migration operations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: skip import with existing good DB" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Probe existing DB - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Post-verify - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y migrate:status"

    "Source database import is set to be skipped. Checking existing database."
    "Using existing migration source database."
    "Verifying migration source database."
    "Starting migrations."

    "- Importing migration source database."
    "- Migration source database is corrupted or empty."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: corrupted DB triggers reimport" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Probe existing DB - fails (corrupted).
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories # 1"

    # Reimport: drop and connect.
    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"

    # Post-verify - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y migrate:status"

    "Source database import is set to be skipped. Checking existing database."
    "Migration source database is corrupted or empty. Re-importing."
    "Importing migration source database."
    "Imported migration source database."
    "Verifying migration source database."

    "- Using existing migration source database."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: missing dump file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  # Do NOT create .data/db2.sql.

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    "Importing migration source database."
    "Migration source database file not found."

    "- Imported migration source database."
    "- Starting migrations."
    "- Finished migration operations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_failure

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: verification failure after import" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Import.
    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"

    # Verification fails.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories # 1"

    # Debug: show tables.
    "@drush -y sql:query --database=migrate SHOW TABLES;"

    "Importing migration source database."
    "Imported migration source database."
    "Verifying migration source database."
    "Migration source database is corrupted."

    "- Enabling migration modules."
    "- Starting migrations."
    "- Finished migration operations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_failure

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: rollback enabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export MIGRATION_SOURCE_DB_IMPORT=1
  export MIGRATION_ROLLBACK_SKIP=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    # Rollback.
    "@drush -y migrate:rollback --all"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y migrate:status"

    "Rolling back all migrations."
    "Running migration: ys_migrate_categories"
    "Finished migration operations."

    "- Skipping rollback of all migrations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/custom/provision-20-migration.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
