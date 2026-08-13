#!/usr/bin/env bats
##
# Unit tests for export-db-file worker script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Mocks `drush` and routes the project binary to it.
#
# With an argument, the mock writes that content into the file named by the
# `--result-file` argument; without one it writes no file at all. The path is
# relative to the Drupal root, so the leading `../` is stripped to resolve it
# from the project root the mock runs in.
mock_drush_dump() {
  local mock
  mock="$(mock_command "drush")"

  if [ "$#" -gt 0 ]; then
    # shellcheck disable=SC2016
    mock_set_side_effect "${mock}" 'for arg in "$@"; do case "${arg}" in --result-file=*) file="${arg#--result-file=}"; printf "%s" "'"${1}"'" >"${file#../}";; esac; done'
  fi

  create_global_command_wrapper "vendor/bin/drush"

  printf '%s\n' "${mock}"
}

@test "export-db-file: Exports cache tables without their data by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_drush=$(mock_drush_dump "dump")

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_success

  assert_output_contains "Started database file export."
  assert_output_contains "Exported database dump saved ./.data/db.sql."
  assert_output_contains "Finished database file export."

  assert_equal "1" "$(mock_get_call_num "${mock_drush}")"
  assert_equal "-y sql:dump --skip-tables-key=common --structure-tables-list=cache* --result-file=../.data/db.sql -q" "$(mock_get_call_args "${mock_drush}" 1)"

  assert_file_exists ".data/db.sql"

  popd >/dev/null
}

@test "export-db-file: Exports to a timestamped file when no argument is given" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_drush=$(mock_drush_dump "dump")

  run .vortex/tooling/src/vortex-export-db-file
  assert_success

  assert_output_contains "Exported database dump saved ./.data/export_db_"
  assert_output_contains "Finished database file export."

  assert_string_contains "--structure-tables-list=cache*" "$(mock_get_call_args "${mock_drush}" 1)"
  assert_string_contains "--result-file=../.data/export_db_" "$(mock_get_call_args "${mock_drush}" 1)"

  assert_file_exists ".data/export_db_*.sql"

  popd >/dev/null
}

@test "export-db-file: Honours a custom structure tables list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_FILE_STRUCTURE_TABLES="cache*,watchdog,sessions"

  mock_drush=$(mock_drush_dump "dump")

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_success

  assert_equal "-y sql:dump --skip-tables-key=common --structure-tables-list=cache*,watchdog,sessions --result-file=../.data/db.sql -q" "$(mock_get_call_args "${mock_drush}" 1)"

  popd >/dev/null
}

@test "export-db-file: Exports the data of every table when the list is empty" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_FILE_STRUCTURE_TABLES=""

  mock_drush=$(mock_drush_dump "dump")

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_success

  assert_equal "-y sql:dump --skip-tables-key=common --structure-tables-list= --result-file=../.data/db.sql -q" "$(mock_get_call_args "${mock_drush}" 1)"

  popd >/dev/null
}

@test "export-db-file: Exports into a custom directory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_EXPORT_DB_FILE_DIR="./.data/custom"

  mock_drush=$(mock_drush_dump "dump")

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_success

  assert_output_contains "Exported database dump saved ./.data/custom/db.sql."
  assert_equal "-y sql:dump --skip-tables-key=common --structure-tables-list=cache* --result-file=../.data/custom/db.sql -q" "$(mock_get_call_args "${mock_drush}" 1)"

  assert_file_exists ".data/custom/db.sql"

  popd >/dev/null
}

@test "export-db-file: Fails when the dump file was not created" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_drush=$(mock_drush_dump)

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_failure

  assert_output_contains "Unable to save dump file ./.data/db.sql."
  assert_output_not_contains "Exported database dump saved"
  assert_output_not_contains "Finished database file export."

  assert_file_not_exists ".data/db.sql"

  popd >/dev/null
}

@test "export-db-file: Fails when the dump file is empty" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_drush=$(mock_drush_dump "")

  run .vortex/tooling/src/vortex-export-db-file db.sql
  assert_failure

  assert_output_contains "Unable to save dump file ./.data/db.sql."
  assert_output_not_contains "Exported database dump saved"

  assert_file_exists ".data/db.sql"

  popd >/dev/null
}
