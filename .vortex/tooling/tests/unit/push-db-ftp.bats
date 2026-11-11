#!/usr/bin/env bats
##
# Unit tests for the 'vortex-push-db-ftp' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Exports the FTP values the database is fetched with.
setup_ftp_fetch_values() {
  export VORTEX_FETCH_DB_FTP_USER="fetch-user"
  export VORTEX_FETCH_DB_FTP_PASS="fetch-pass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="db.sql"
  unset VORTEX_PUSH_DB_FTP_USER VORTEX_PUSH_DB_FTP_PASS VORTEX_PUSH_DB_FTP_HOST VORTEX_PUSH_DB_FTP_PORT VORTEX_PUSH_DB_FTP_FILE VORTEX_PUSH_DB_FILE_PREFIX
}

@test "push-db-ftp: Upload the dump and keep the dump it replaces" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "CREATE TABLE test (id INT);" >.data/db.sql

  setup_ftp_fetch_values

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "cat >${BUILD_DIR}/curl-config" 1

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_success
  assert_output_contains "[INFO] Started database dump push to FTP."
  assert_output_contains "Local file:    ./.data/db.sql"
  assert_output_contains "Remote file:   db.sql"
  assert_output_contains "Previous file: db-previous.sql"
  assert_output_contains "FTP host:      ftp.example.com:21"
  assert_output_contains "[ OK ] Uploaded the database dump file and kept the dump it replaced."
  assert_output_contains "[ OK ] Finished database dump push to FTP."
  assert_output_not_contains "fetch-pass"

  assert_equal "1" "$(mock_get_call_num "${mock_curl}")"

  args="$(mock_get_call_args "${mock_curl}" 1)"
  assert_string_contains "${args}" "--config - --ftp-create-dirs --upload-file ./.data/db.sql"
  # The upload takes a temporary name, and the rename into place comes last.
  assert_string_contains "${args}" "--quote -*DELE db-previous.sql --quote -*RNFR db.sql --quote -*RNTO db-previous.sql --quote -RNFR db.sql.uploading --quote -RNTO db.sql"
  assert_string_contains "${args}" "ftp://ftp.example.com:21/db.sql.uploading"
  # The credentials reach curl on stdin, never as arguments.
  assert_string_not_contains "${args}" "fetch-pass"
  assert_file_contains "${BUILD_DIR}/curl-config" 'user = "fetch-user:fetch-pass"'

  rm -rf .data

  popd >/dev/null
}

@test "push-db-ftp: Push to a remote directory with a file name prefix" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  setup_ftp_fetch_values
  export VORTEX_PUSH_DB_FTP_HOST="push.example.com"
  export VORTEX_PUSH_DB_FTP_PORT="2121"
  export VORTEX_PUSH_DB_FTP_FILE="/backups/site/db.sql"
  export VORTEX_PUSH_DB_FILE_PREFIX="gha-"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_success
  assert_output_contains "Remote file:   backups/site/gha-db.sql"
  assert_output_contains "Previous file: backups/site/gha-db-previous.sql"
  assert_output_contains "FTP host:      push.example.com:2121"

  args="$(mock_get_call_args "${mock_curl}" 1)"
  # The rename commands run in the remote directory, so they use bare names.
  assert_string_contains "${args}" "--quote -*DELE gha-db-previous.sql --quote -*RNFR gha-db.sql --quote -*RNTO gha-db-previous.sql --quote -RNFR gha-db.sql.uploading --quote -RNTO gha-db.sql"
  assert_string_contains "${args}" "ftp://push.example.com:2121/backups/site/gha-db.sql.uploading"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-ftp: Push an indexed database with its own variables and file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "migration content" >.data/db2.sql

  setup_ftp_fetch_values
  export VORTEX_DB_INDEX=2
  export VORTEX_FETCH_DB2_FTP_USER="db2-user"
  export VORTEX_FETCH_DB2_FTP_PASS="db2-pass"
  export VORTEX_FETCH_DB2_FTP_HOST="ftp2.example.com"
  export VORTEX_FETCH_DB2_FTP_PORT="21"
  export VORTEX_FETCH_DB2_FTP_FILE="db2.sql"
  export VORTEX_FETCH_DB2_FILE="db2.sql"

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "cat >${BUILD_DIR}/curl-config" 1

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_success
  assert_output_contains "[INFO] Started database 2 dump push to FTP."
  assert_output_contains "Local file:    ./.data/db2.sql"
  assert_output_contains "Remote file:   db2.sql"
  assert_output_contains "Previous file: db2-previous.sql"
  assert_output_contains "[ OK ] Finished database 2 dump push to FTP."

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 1)" "ftp://ftp2.example.com:21/db2.sql.uploading"
  assert_file_contains "${BUILD_DIR}/curl-config" 'user = "db2-user:db2-pass"'

  rm -rf .data

  popd >/dev/null
}

@test "push-db-ftp: Escape quotes and backslashes in the credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  setup_ftp_fetch_values
  export VORTEX_FETCH_DB_FTP_PASS='pa"ss\word'

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "cat >${BUILD_DIR}/curl-config" 1

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_success

  assert_file_contains "${BUILD_DIR}/curl-config" 'user = "fetch-user:pa\"ss\\word"'

  rm -rf .data

  popd >/dev/null
}

@test "push-db-ftp: Fail when a required value is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  mock_curl=$(mock_command "curl")

  for name in USER PASS HOST FILE; do
    setup_ftp_fetch_values
    export "VORTEX_FETCH_DB_FTP_${name}="

    run .vortex/tooling/src/vortex-push-db-ftp
    assert_failure
    assert_output_contains "[FAIL] Missing required value for VORTEX_PUSH_DB_FTP_${name} or VORTEX_FETCH_DB_FTP_${name}."
  done

  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-ftp: Fail when the database dump file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data

  setup_ftp_fetch_values

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Database dump file ./.data/db.sql does not exist."
  assert_output_not_contains "Started database dump push to FTP."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "push-db-ftp: Fail when the upload or a rename fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  setup_ftp_fetch_values

  mock_curl=$(mock_command "curl")
  mock_set_status "${mock_curl}" 21 1

  run .vortex/tooling/src/vortex-push-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Unable to push the database dump file to FTP."
  assert_output_not_contains "Finished database dump push to FTP."

  rm -rf .data

  popd >/dev/null
}
