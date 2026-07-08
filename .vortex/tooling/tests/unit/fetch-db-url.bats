#!/usr/bin/env bats
##
# Unit tests for fetch-db-url.sh
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

@test "fetch-db-url: Fetch non-zip file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1

  export VORTEX_FETCH_DB_URL="http://example.com/db.sql"
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_contains "Fetching database dump file."
  assert_output_contains "[ OK ] Finished database dump fetch from URL."

  popd >/dev/null
}

@test "fetch-db-url: Resolve indexed dir from plain VORTEX_DB_DIR fallback" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p custom_data && touch custom_data/db2.sql" 1

  # The migration DB (index 2) has no per-index directory in the fixture, so the
  # non-indexed VORTEX_DB_DIR base must be used for the fetch target directory.
  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_URL="http://example.com/db.sql"
  export VORTEX_DB_DIR="custom_data"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  # curl writes into custom_data, resolved via the plain VORTEX_DB_DIR fallback.
  assert_contains "custom_data/" "$(mock_get_call_args "${mock_curl}" 1)"

  popd >/dev/null
}

@test "fetch-db-url: Resolve indexed file name from plain VORTEX_DB_FILE fallback" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/custom.sql" 1

  # An index with no per-index file override must fall back to the non-indexed
  # VORTEX_DB_FILE for the fetch target file name.
  export VORTEX_DB_INDEX="3"
  export VORTEX_FETCH_DB3_URL="http://example.com/db.sql"
  export VORTEX_DB_FILE="custom.sql"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  # curl writes custom.sql, resolved via the plain VORTEX_DB_FILE fallback.
  assert_contains "/custom.sql" "$(mock_get_call_args "${mock_curl}" 1)"

  popd >/dev/null
}

@test "fetch-db-url: Fetch zip file without password" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1
  mock_unzip=$(mock_command "unzip")
  mock_set_side_effect "${mock_unzip}" "mkdir -p \"\$4/subdir\" && echo 'database content' > \"\$4/subdir/backup.sql\"" 1
  mock_find=$(mock_command "find")
  mock_set_side_effect "${mock_find}" 'echo "$1/subdir/backup.sql"' 1

  export VORTEX_FETCH_DB_URL="http://example.com/db.zip"
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"
  export VORTEX_FETCH_DB_UNZIP_PASSWORD=""

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_contains "Unzipping database dump file."
  assert_output_contains "[ OK ] Finished database dump fetch from URL."

  popd >/dev/null
}

@test "fetch-db-url: Fetch zip file with password" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1
  mock_unzip=$(mock_command "unzip")
  mock_set_side_effect "${mock_unzip}" "mkdir -p \"\$6/protected\" && echo 'protected database content' > \"\$6/protected/secure_backup.sql\"" 1
  mock_find=$(mock_command "find")
  mock_set_side_effect "${mock_find}" 'echo "$1/protected/secure_backup.sql"' 1

  export VORTEX_FETCH_DB_URL="http://example.com/protected.zip"
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"
  export VORTEX_FETCH_DB_UNZIP_PASSWORD="secret123"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_contains "Unzipping password-protected database dump file."
  assert_output_contains "[ OK ] Finished database dump fetch from URL."

  popd >/dev/null
}

@test "fetch-db-url: Fail when VORTEX_FETCH_DB_URL is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_URL=""
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_failure
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_URL."

  popd >/dev/null
}

@test "fetch-db-url: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p ./.data && touch ./.data/db.sql" 1

  export VORTEX_FETCH_DB_URL="http://example.com/test.sql"
  # Don't set VORTEX_FETCH_DB_URL_DB_DIR and VORTEX_FETCH_DB_URL_DB_FILE to test defaults
  unset VORTEX_FETCH_DB_URL_DB_DIR VORTEX_FETCH_DB_URL_DB_FILE VORTEX_FETCH_DB_UNZIP_PASSWORD

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_contains "Fetching database dump file."
  assert_output_contains "[ OK ] Finished database dump fetch from URL."

  popd >/dev/null
}

@test "fetch-db-url: Curl uses fail flag to error on HTTP errors" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_side_effect "${mock_curl}" "mkdir -p .data && touch .data/db.sql" 1

  export VORTEX_FETCH_DB_URL="http://example.com/db.sql"
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_success
  # The -f flag makes curl exit non-zero on HTTP 4xx/5xx instead of writing
  # the error body into the dump file and reporting success.
  assert_contains "-fLs" "$(mock_get_call_args "${mock_curl}" 1)"

  popd >/dev/null
}

@test "fetch-db-url: Fail when curl errors on an HTTP error" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # With -f, curl exits non-zero (22) on an HTTP 4xx/5xx; set -e must abort the
  # script instead of leaving a corrupt dump and reporting success.
  mock_curl=$(mock_command "curl")
  mock_set_status "${mock_curl}" 22 1

  export VORTEX_FETCH_DB_URL="http://example.com/missing.sql"
  export VORTEX_FETCH_DB_URL_DB_DIR=".data"
  export VORTEX_FETCH_DB_URL_DB_FILE="db.sql"

  run .vortex/tooling/src/vortex-fetch-db-url
  assert_failure
  assert_output_contains "[INFO] Started database dump fetch from URL."
  assert_output_not_contains "[ OK ] Finished database dump fetch from URL."

  popd >/dev/null
}
