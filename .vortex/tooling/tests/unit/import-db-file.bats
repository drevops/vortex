#!/usr/bin/env bats
##
# Unit tests for import-db-file worker script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "import-db-file: Imports from the provided dump file argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p "./.data"
  touch "./.data/db_custom.sql"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y sql:drop"
    "@drush -y sql:connect"
    "Started database file import."
    "Imported database from the dump file."
    "Finished database file import."
    "- Unable to import database from file."
  )

  mocks="$(run_steps "setup")"

  run .vortex/tooling/src/import-db-file .data/db_custom.sql
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "import-db-file: Imports from the default dump file when no argument is given" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env files to test default resolution in isolation.
  rm -f ./.env ./.env.local
  touch ./.env
  unset VORTEX_IMPORT_DB_FILE_DIR VORTEX_IMPORT_DB_FILE VORTEX_DB_DIR VORTEX_DB_FILE

  mkdir -p "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y sql:drop"
    "@drush -y sql:connect"
    "Imported database from the dump file."
    "Finished database file import."
  )

  mocks="$(run_steps "setup")"

  run .vortex/tooling/src/import-db-file
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "import-db-file: Fails when the dump file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/import-db-file .data/missing.sql
  assert_failure

  assert_output_contains "Unable to import database from file."
  assert_output_contains "Dump file .data/missing.sql does not exist."
  assert_output_contains "Site content was not changed."
  assert_output_not_contains "Imported database from the dump file."
  assert_output_not_contains "Finished database file import."

  popd >/dev/null
}

@test "import-db-file: Fails when the dump file is not readable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Root bypasses file permission bits, so the unreadable case cannot be
  # exercised when the suite runs as root.
  [ "$(id -u)" = "0" ] && skip "Root bypasses file read permissions."

  mkdir -p "./.data"
  touch "./.data/db.sql"
  chmod 000 "./.data/db.sql"

  run .vortex/tooling/src/import-db-file .data/db.sql
  assert_failure

  assert_output_contains "Unable to import database from file."
  assert_output_contains "Dump file .data/db.sql is not readable."
  assert_output_contains "Site content was not changed."
  assert_output_not_contains "Imported database from the dump file."

  popd >/dev/null
}
