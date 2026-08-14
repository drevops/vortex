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

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Import: drop and connect.
    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"

    # Verification after import.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Search indexes are disabled for the duration of the migration.
    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Migration: reset, import, status.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y search-api:enable content"
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
    "Disabling search indexes."
    "Disabled search indexes."
    "Skipped rollback of all migrations."
    "Running migration: ys_migrate_categories"
    "Finished migrations."
    "Enabling search indexes."
    "Enabled search indexes."
    "Finished migration operations."

    # Not expected.
    "- Skipped migrations. DRUPAL_MIGRATION_SKIP is set to 1."
    "- Using existing migration source database."
    "- Migration source database is corrupted."
    "- Rolling back all migrations."
    "- Migration source database file not found."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: skip all migrations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SKIP=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "Started migration operations."
    "Migration skip:          1"
    "Skipped migrations. DRUPAL_MIGRATION_SKIP is set to 1."

    "- Importing migration source database."
    "- Starting migrations."
    "- Disabling search indexes."
    "- Running migration:"
    "- Finished migrations."
    "- Enabling search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: production environment auto-skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # prod"

    "Started migration operations."
    "Environment: prod"
    "Migration skip:          1"
    "Skipped migrations. DRUPAL_MIGRATION_SKIP is set to 1."

    "- Importing migration source database."
    "- Starting migrations."
    "- Disabling search indexes."
    "- Enabling search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: skip import with existing good DB" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Probe existing DB - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Post-verify - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Search indexes are disabled for the duration of the migration.
    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y search-api:enable content"
    "@drush -y migrate:status"

    "Source database import is set to be skipped. Checking existing database."
    "Using existing migration source database."
    "Verifying migration source database."
    "Starting migrations."
    "Disabled search indexes."
    "Enabled search indexes."

    "- Importing migration source database."
    "- Migration source database is corrupted or empty."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: corrupted DB triggers reimport" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Probe existing DB - fails (corrupted).
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories # 1"

    # Reimport: drop and connect.
    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"

    # Post-verify - succeeds.
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"

    # Enable migration module.
    "@drush -y pm:install ys_migrate"

    # Search indexes are disabled for the duration of the migration.
    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y search-api:enable content"
    "@drush -y migrate:status"

    "Source database import is set to be skipped. Checking existing database."
    "Migration source database is corrupted or empty. Re-importing."
    "Importing migration source database."
    "Imported migration source database."
    "Verifying migration source database."

    "- Using existing migration source database."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: missing dump file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  # Do NOT create .data/db2.sql.

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "Importing migration source database."
    "Migration source database file not found."

    "- Imported migration source database."
    "- Starting migrations."
    "- Disabling search indexes."
    "- Enabling search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: verification failure after import" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

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
    "- Disabling search indexes."
    "- Enabling search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: no enabled search indexes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    # No index is enabled, so none is disabled or restored.
    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE])));"

    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y migrate:status"

    "Migrated: ys_migrate_categories."
    "Finished migration operations."

    "- Disabling search indexes."
    "- Disabled search indexes."
    "- Enabling search indexes."
    "- Enabled search indexes."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: partly failed disable restores the disabled indexes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content archive"

    # The second index fails to disable.
    "@drush -y search-api:disable content"
    "@drush -y search-api:disable archive # 1"

    # Both indexes are enabled again on the way out.
    "@drush -y search-api:enable content"
    "@drush -y search-api:enable archive"

    "Disabling search indexes."
    "Failed to disable search indexes."
    "Enabled search indexes."

    "- Disabled search indexes."
    "- Running migration:"
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: failed migration re-enables search indexes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Migration fails.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories # 1"
    "@drush -y migrate:messages ys_migrate_categories"

    # Indexes are restored on the way out.
    "@drush -y search-api:enable content"

    "Disabled search indexes."
    "Failed to run migration ys_migrate_categories."
    "Enabling search indexes."
    "Enabled search indexes."

    "- Migrated: ys_migrate_categories."
    "- Finished migrations."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: failed search index restore reports and keeps the migration failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Both the migration and the restore fail.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories # 1"
    "@drush -y migrate:messages ys_migrate_categories"
    "@drush -y search-api:enable content # 1"

    "Failed to run migration ys_migrate_categories."
    "Failed to enable search indexes."

    "- Enabled search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: failed search index restore fails a successful migration" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"

    # The restore fails after the migrations succeeded.
    "@drush -y search-api:enable content # 1"

    "Migrated: ys_migrate_categories."
    "Finished migrations."
    "Enabling search indexes."
    "Failed to enable search indexes."

    "- Enabled search indexes."
    "- Finished migration operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision migration: rollback enabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  mkdir -p "./.data"
  touch "./.data/db2.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_MIGRATION_SOURCE_DB_IMPORT=1
  export DRUPAL_MIGRATION_ROLLBACK_SKIP=0

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y sql:drop --database=migrate"
    "@drush -y sql:connect --database=migrate"
    "@drush -y sql:query --database=migrate SELECT COUNT(*) FROM categories"
    "@drush -y pm:install ys_migrate"

    # Search indexes are disabled before the rollback, which also writes
    # entities.
    "@drush -y php:eval print implode(' ', array_keys(\Drupal::entityTypeManager()->getStorage('search_api_index')->loadByProperties(['status' => TRUE]))); # content"
    "@drush -y search-api:disable content"

    # Rollback.
    "@drush -y migrate:rollback --all"

    # Migration.
    "@drush -y migrate:reset-status ys_migrate_categories"
    "@drush -y migrate:import --feedback=50 --limit=50 ys_migrate_categories"
    "@drush -y search-api:enable content"
    "@drush -y migrate:status"

    "Rolling back all migrations."
    "Running migration: ys_migrate_categories"
    "Finished migration operations."

    "- Skipped rollback of all migrations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-20-migration.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
