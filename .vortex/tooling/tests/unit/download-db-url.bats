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

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/db.sql"
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/download-db-url
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

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/db.zip"
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"
  export VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD=""

  run .vortex/tooling/src/download-db-url
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

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/protected.zip"
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"
  export VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD="secret123"

  run .vortex/tooling/src/download-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Unzipping password-protected database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}

@test "download-db-url: Fail when VORTEX_DOWNLOAD_DB_URL is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DOWNLOAD_DB_URL=""
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/download-db-url
  assert_failure
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_URL."

  popd >/dev/null
}

@test "download-db-url: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p ./.data && touch ./.data/db.sql" 1

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/test.sql"
  # Don't set VORTEX_DOWNLOAD_DB_URL_DB_DIR and VORTEX_DOWNLOAD_DB_URL_DB_FILE to test defaults
  unset VORTEX_DOWNLOAD_DB_URL_DB_DIR VORTEX_DOWNLOAD_DB_URL_DB_FILE VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD

  run .vortex/tooling/src/download-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_contains "Downloading database dump file."
  assert_output_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}

@test "download-db-url: Curl uses fail flag to error on HTTP errors" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/db.sql"
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/download-db-url
  assert_success
  # The -f flag makes curl exit non-zero on HTTP 4xx/5xx instead of writing
  # the error body into the dump file and reporting success.
  assert_contains "-fLs" "$(mock_get_call_args "${mock_curl}" 1)"

  popd >/dev/null
}

@test "download-db-url: Fail when curl errors on an HTTP error" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # With -f, curl exits non-zero (22) on an HTTP 4xx/5xx; set -e must abort the
  # script instead of leaving a corrupt dump and reporting success.
  mock_curl=$(mock_command "curl")
  mock_set_status "${mock_curl}" 22 1

  export VORTEX_DOWNLOAD_DB_URL="http://example.com/missing.sql"
  export VORTEX_DOWNLOAD_DB_URL_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/download-db-url
  assert_failure
  assert_output_contains "[INFO] Started database dump download from URL."
  assert_output_not_contains "[ OK ] Finished database dump download from URL."

  popd >/dev/null
}
