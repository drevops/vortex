#!/usr/bin/env bats
##
# Unit tests for the 'vortex-fetch-db-ftp' script.
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
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

  run .vortex/tooling/src/vortex-fetch-db-ftp
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_FTP_FILE."

  popd >/dev/null
}

@test "fetch-db-ftp: Join the remote directory to the file name with any slashes" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="db.sql"
  export VORTEX_FETCH_DB_FTP_DB_DIR=".data"

  mock_curl=$(mock_command "curl")

  call=0
  for dir in "nightly/gha" "/nightly/gha" "nightly/gha/" "/nightly/gha/" "//nightly/gha//"; do
    export VORTEX_FETCH_DB_FTP_DIR="${dir}"
    call=$((call + 1))

    run .vortex/tooling/src/vortex-fetch-db-ftp
    assert_success
    assert_output_contains "Remote file: nightly/gha/db.sql"
    assert_string_contains "$(mock_get_call_args "${mock_curl}" "${call}")" "ftp://ftp.example.com:21/nightly/gha/db.sql "
  done

  # A leading slash on the file name joins without a double slash.
  export VORTEX_FETCH_DB_FTP_DIR="nightly"
  export VORTEX_FETCH_DB_FTP_FILE="/db.sql"
  run .vortex/tooling/src/vortex-fetch-db-ftp
  assert_success
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 6)" "ftp://ftp.example.com:21/nightly/db.sql "

  popd >/dev/null
}

@test "fetch-db-ftp: Fetch from the root directory without a remote directory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_FTP_USER="testuser"
  export VORTEX_FETCH_DB_FTP_PASS="testpass"
  export VORTEX_FETCH_DB_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB_FTP_PORT="21"
  export VORTEX_FETCH_DB_FTP_FILE="backup/db.sql"
  export VORTEX_FETCH_DB_FTP_DB_DIR=".data"

  mock_curl=$(mock_command "curl")

  call=0
  for dir in "" "/" "//"; do
    export VORTEX_FETCH_DB_FTP_DIR="${dir}"
    call=$((call + 1))

    run .vortex/tooling/src/vortex-fetch-db-ftp
    assert_success
    assert_output_contains "Remote file: backup/db.sql"
    assert_string_contains "$(mock_get_call_args "${mock_curl}" "${call}")" "ftp://ftp.example.com:21/backup/db.sql "
  done

  popd >/dev/null
}

@test "fetch-db-ftp: Use the indexed remote directory with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_INDEX=2
  export VORTEX_FETCH_DB2_FTP_USER="testuser"
  export VORTEX_FETCH_DB2_FTP_PASS="testpass"
  export VORTEX_FETCH_DB2_FTP_HOST="ftp.example.com"
  export VORTEX_FETCH_DB2_FTP_PORT="21"
  export VORTEX_FETCH_DB2_FTP_FILE="db2.sql"
  export VORTEX_FETCH_DB2_FTP_DIR="/migration/"
  export VORTEX_FETCH_DB_FTP_DIR="primary"
  export VORTEX_FETCH_DB2_DIR=".data"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-fetch-db-ftp
  assert_success
  assert_output_contains "Remote file: migration/db2.sql"
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 1)" "ftp://ftp.example.com:21/migration/db2.sql "

  popd >/dev/null
}
