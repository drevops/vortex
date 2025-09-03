#!/usr/bin/env bats
##
# Unit tests for download-db-url.sh
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

@test "download-db-url: Download non-zip file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1

  export VORTEX_DB_DOWNLOAD_URL="http://example.com/db.sql"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  run scripts/vortex/download-db-url.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Downloading database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}

@test "download-db-url: Download zip file without password" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1
  mock_unzip=$(mock_command "unzip")
  mock_set_side_effect "${mock_unzip}" "mkdir -p \"\$4/subdir\" && echo 'database content' > \"\$4/subdir/backup.sql\"" 1
  mock_find=$(mock_command "find")
  mock_set_side_effect "${mock_find}" 'echo "$1/subdir/backup.sql"' 1

  export VORTEX_DB_DOWNLOAD_URL="http://example.com/db.zip"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_UNZIP_PASSWORD=""

  run scripts/vortex/download-db-url.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Unzipping database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}

@test "download-db-url: Download zip file with password" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1
  mock_unzip=$(mock_command "unzip")
  mock_set_side_effect "${mock_unzip}" "mkdir -p \"\$6/protected\" && echo 'protected database content' > \"\$6/protected/secure_backup.sql\"" 1
  mock_find=$(mock_command "find")
  mock_set_side_effect "${mock_find}" 'echo "$1/protected/secure_backup.sql"' 1

  export VORTEX_DB_DOWNLOAD_URL="http://example.com/protected.zip"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_UNZIP_PASSWORD="secret123"

  run scripts/vortex/download-db-url.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Unzipping password-protected database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}

@test "download-db-url: Fail when VORTEX_DB_DOWNLOAD_URL is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_DOWNLOAD_URL=""
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  run scripts/vortex/download-db-url.sh
  assert_failure
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "[FAIL] Missing required value for VORTEX_DB_DOWNLOAD_URL."

  popd >/dev/null
}

@test "download-db-url: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p ./.data && touch ./.data/db.sql" 1

  export VORTEX_DB_DOWNLOAD_URL="http://example.com/test.sql"
  # Don't set VORTEX_DB_DIR and VORTEX_DB_FILE to test defaults
  unset VORTEX_DB_DIR VORTEX_DB_FILE VORTEX_DB_DOWNLOAD_UNZIP_PASSWORD

  run scripts/vortex/download-db-url.sh
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Downloading database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}
