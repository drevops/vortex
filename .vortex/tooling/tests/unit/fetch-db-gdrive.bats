#!/usr/bin/env bats
##
# Unit tests for the 'vortex-fetch-db-gdrive' script.
#
# shellcheck disable=SC2016,SC2030,SC2031,SC2034

load ../_helper.bash

# Writes a service account key file with a newly generated private key.
fixture_gdrive_key() {
  openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out "${BUILD_DIR}/gdrive.pem" 2>/dev/null
  php -r 'print json_encode(["type" => "service_account", "client_email" => "vortex@example.iam.gserviceaccount.com", "private_key" => file_get_contents($argv[1])]);' "${BUILD_DIR}/gdrive.pem" >"${BUILD_DIR}/gdrive.json"
}

@test "fetch-db-gdrive: Fetch database file successfully with a key file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  fixture_gdrive_key

  unset VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY
  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_FETCH_DB_GDRIVE_DB_DIR=".data"
  export VORTEX_FETCH_DB_GDRIVE_DB_FILE="db.sql"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token","expires_in":3599}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-456"}]}' 2
  mock_set_side_effect "${mock_curl}" "echo 'CREATE TABLE test (id INT);' > .data/db.sql" 3

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_success
  assert_output_contains "[INFO] Started database dump fetch from Google Drive."
  assert_output_contains "Remote file: db.sql"
  assert_output_contains "Local path:  .data/db.sql"
  assert_output_contains "Folder ID:   folder-123"
  assert_output_contains "[ OK ] Authenticated with the service account."
  assert_output_contains "[ OK ] Found the database dump file with ID file-456."
  assert_output_contains "[ OK ] Downloaded the database dump file."
  assert_output_contains "[ OK ] Finished database dump fetch from Google Drive."
  assert_output_not_contains "test-token"

  assert_equal "3" "$(mock_get_call_num "${mock_curl}")"

  # The token request carries a signed JWT assertion for the read-only scope.
  token_args="$(mock_get_call_args "${mock_curl}" 1)"
  assert_string_contains "${token_args}" "https://oauth2.googleapis.com/token"
  assert_string_contains "${token_args}" "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer"
  assertion="${token_args##*assertion=}"
  assertion="${assertion%% *}"
  IFS='.' read -r jwt_header jwt_claims jwt_signature <<<"${assertion}"
  assert_not_empty "${jwt_signature}"
  claims="$(php -r 'print base64_decode(strtr($argv[1], "-_", "+/"));' "${jwt_claims}")"
  assert_string_contains "${claims}" '"iss":"vortex@example.iam.gserviceaccount.com"'
  assert_string_contains "${claims}" '"scope":"https:\/\/www.googleapis.com\/auth\/drive.readonly"'

  list_args="$(mock_get_call_args "${mock_curl}" 2)"
  assert_string_contains "${list_args}" "https://www.googleapis.com/drive/v3/files"
  assert_string_contains "${list_args}" "Authorization: Bearer test-token"
  assert_string_contains "${list_args}" "q=name = 'db.sql' and 'folder-123' in parents and trashed = false"
  assert_string_contains "${list_args}" "supportsAllDrives=true"

  download_args="$(mock_get_call_args "${mock_curl}" 3)"
  assert_string_contains "${download_args}" "Authorization: Bearer test-token"
  assert_string_contains "${download_args}" "-o .data/db.sql"
  assert_string_contains "${download_args}" "https://www.googleapis.com/drive/v3/files/file-456?alt=media&supportsAllDrives=true"

  assert_file_contains ".data/db.sql" "CREATE TABLE test"

  rm -rf .data

  popd >/dev/null
}

@test "fetch-db-gdrive: Fetch database file with the key content in the shared variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY=""
  VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY="$(cat "${BUILD_DIR}/gdrive.json")"
  export VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"
  unset VORTEX_FETCH_DB_GDRIVE_DB_DIR VORTEX_FETCH_DB_GDRIVE_DB_FILE

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-456"}]}' 2
  mock_set_status "${mock_curl}" 0 3

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_success
  # The default directory and file name come from .env.
  assert_output_contains "Local path:  ./.data/db.sql"
  assert_output_contains "[ OK ] Finished database dump fetch from Google Drive."

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 3)" "-o ./.data/db.sql"

  rm -rf .data

  popd >/dev/null
}

@test "fetch-db-gdrive: Resolve indexed variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  fixture_gdrive_key

  unset VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY
  export VORTEX_DB_INDEX=3
  export VORTEX_FETCH_DB3_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB3_GDRIVE_FOLDER_ID="folder-789"
  export VORTEX_FETCH_DB3_FILE="db3.sql"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-3"}]}' 2
  mock_set_status "${mock_curl}" 0 3

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_success
  assert_output_contains "Remote file: db3.sql"
  assert_output_contains "Folder ID:   folder-789"

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 2)" "q=name = 'db3.sql' and 'folder-789' in parents and trashed = false"

  rm -rf .data

  popd >/dev/null
}

@test "fetch-db-gdrive: Escape a quote in the file name of the folder query" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_FETCH_DB_GDRIVE_DB_FILE="site's.sql"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-456"}]}' 2
  mock_set_status "${mock_curl}" 0 3

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_success

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 2)" "q=name = 'site\\'s.sql' and"

  rm -rf .data

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the service account key is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY=""
  export VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY=""
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY or VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the folder ID is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="{}"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID=""

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_GDRIVE_FOLDER_ID."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the service account key is invalid" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")

  for key in "not json" '{"client_email":"vortex@example.iam.gserviceaccount.com"}' '{"client_email":"vortex@example.iam.gserviceaccount.com","private_key":"not a key"}'; do
    export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${key}"

    run .vortex/tooling/src/vortex-fetch-db-gdrive
    assert_failure
    assert_output_contains "[FAIL] Unable to authenticate with the service account. Check that VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY or VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY is a valid service account JSON key."
  done

  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the token request is rejected" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"error":"invalid_grant","error_description":"Invalid JWT Signature."}' 1

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to authenticate with the service account."
  assert_output_not_contains "Found the database dump file"
  assert_equal "1" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the folder cannot be listed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"error":{"code":404,"message":"File not found: folder-123."}}' 2

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to list the folder-123 folder. Check that the folder ID is correct and that the folder is shared with the service account."
  assert_equal "2" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the folder has no database dump file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_FETCH_DB_GDRIVE_DB_FILE="db.sql"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to find the db.sql file in the folder-123 folder."
  assert_equal "2" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "fetch-db-gdrive: Fail when the download fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  fixture_gdrive_key

  export VORTEX_FETCH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-456"}]}' 2
  mock_set_status "${mock_curl}" 22 3

  run .vortex/tooling/src/vortex-fetch-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to download the database dump file."
  assert_output_not_contains "Finished database dump fetch from Google Drive."

  rm -rf .data

  popd >/dev/null
}
