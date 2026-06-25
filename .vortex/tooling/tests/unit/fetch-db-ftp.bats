#!/usr/bin/env bats
##
# Unit tests for fetch-db-ftp.sh
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "fetch-db-ftp: Fetch database file successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && echo 'database content' > .data/db.sql" 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"
  export VORTEX_FETCH_DB_FTP_DB_DIR=".data"
  export VORTEX_FETCH_DB_FTP_DB_FILE="db.sql"

  run .vortex/tooling/src/fetch-db-ftp
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from FTP."
  assert_output_contains "[ OK ] Finished database dump fetch from FTP."

  popd >/dev/null
}

@test "fetch-db-ftp: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p ./.data && echo 'database content' > ./.data/db.sql" 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/database.sql"
  # Don't set VORTEX_FETCH_DB_FTP_DB_DIR and VORTEX_FETCH_DB_FTP_DB_FILE to test defaults
  unset VORTEX_FETCH_DB_FTP_DB_DIR VORTEX_FETCH_DB_FTP_DB_FILE

  run .vortex/tooling/src/fetch-db-ftp
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from FTP."
  assert_output_contains "[ OK ] Finished database dump fetch from FTP."

  popd >/dev/null
}

@test "fetch-db-ftp: Fail when VORTEX_FETCH_DB_FTP_USER is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER=""
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"

  run .vortex/tooling/src/fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_USER."

  popd >/dev/null
}

@test "fetch-db-ftp: Fail when VORTEX_FETCH_DB_FTP_PASS is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS=""
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"

  run .vortex/tooling/src/fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_PASS."

  popd >/dev/null
}

@test "fetch-db-ftp: Fail when VORTEX_FETCH_DB_FTP_HOST is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST=""
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"

  run .vortex/tooling/src/fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_HOST."

  popd >/dev/null
}

@test "fetch-db-ftp: Fail when VORTEX_FETCH_DB_FTP_PORT is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT=""
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"

  run .vortex/tooling/src/fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_PORT."

  popd >/dev/null
}

@test "fetch-db-ftp: Fail when VORTEX_FETCH_DB_FTP_FILE is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE=""

  run .vortex/tooling/src/fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_FILE."

  popd >/dev/null
}
