#!/usr/bin/env bats
##
# Unit tests for download-db-ftp.sh
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "download-db-ftp: Download database file successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && echo 'database content' > .data/db.sql" 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/db.sql"
  export VORTEX_DOWNLOAD_DB_FTP_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_FTP_DB_FILE="db.sql"

  run scripts/vortex/download-db-ftp.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from FTP."
  assert_output_contains "[ OK ] Finished database dump download from FTP."

  popd >/dev/null
}

@test "download-db-ftp: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p ./.data && echo 'database content' > ./.data/db.sql" 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/database.sql"
  # Don't set VORTEX_DOWNLOAD_DB_FTP_DB_DIR and VORTEX_DOWNLOAD_DB_FTP_DB_FILE to test defaults
  unset VORTEX_DOWNLOAD_DB_FTP_DB_DIR VORTEX_DOWNLOAD_DB_FTP_DB_FILE

  run scripts/vortex/download-db-ftp.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from FTP."
  assert_output_contains "[ OK ] Finished database dump download from FTP."

  popd >/dev/null
}

@test "download-db-ftp: Fail when VORTEX_DOWNLOAD_DB_FTP_USER is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_FTP_USER=""
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/db.sql"

  run scripts/vortex/download-db-ftp.sh
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_FTP_USER."

  popd >/dev/null
}

@test "download-db-ftp: Fail when VORTEX_DOWNLOAD_DB_FTP_PASS is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS=""
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/db.sql"

  run scripts/vortex/download-db-ftp.sh
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_FTP_PASS."

  popd >/dev/null
}

@test "download-db-ftp: Fail when VORTEX_DOWNLOAD_DB_FTP_HOST is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST=""
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/db.sql"

  run scripts/vortex/download-db-ftp.sh
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_FTP_HOST."

  popd >/dev/null
}

@test "download-db-ftp: Fail when VORTEX_DOWNLOAD_DB_FTP_PORT is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT=""
  export VORTEX_DOWNLOAD_DB_FTP_FILE="backup/db.sql"

  run scripts/vortex/download-db-ftp.sh
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_FTP_PORT."

  popd >/dev/null
}

@test "download-db-ftp: Fail when VORTEX_DOWNLOAD_DB_FTP_FILE is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_FTP_USER="testuser"
  export VORTEX_DOWNLOAD_DB_FTP_PASS="testpass"
  export VORTEX_DOWNLOAD_DB_FTP_HOST="ftp.example.com"
  export VORTEX_DOWNLOAD_DB_FTP_PORT="21"
  export VORTEX_DOWNLOAD_DB_FTP_FILE=""

  run scripts/vortex/download-db-ftp.sh
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_FTP_FILE."

  popd >/dev/null
}
