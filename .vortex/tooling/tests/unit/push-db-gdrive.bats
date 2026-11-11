#!/usr/bin/env bats
##
# Unit tests for the 'vortex-push-db-gdrive' script.
#
# shellcheck disable=SC2016,SC2030,SC2031,SC2034

load ../_helper.bash

# Writes a service account key file with a newly generated private key.
fixture_gdrive_key() {
  openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out "${BUILD_DIR}/gdrive.pem" 2>/dev/null
  php -r 'print json_encode(["type" => "service_account", "client_email" => "vortex@example.iam.gserviceaccount.com", "private_key" => file_get_contents($argv[1])]);' "${BUILD_DIR}/gdrive.pem" >"${BUILD_DIR}/gdrive.json"
}

@test "push-db-gdrive: Create the database dump file in the folder" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "CREATE TABLE test (id INT);" >.data/db.sql
  fixture_gdrive_key

  unset VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY
  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"
  export VORTEX_PUSH_DB_GDRIVE_DB_FILE="db.sql"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 200\r\nlocation: https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session-1\r\ncontent-length: 0\r\n' 3
  mock_set_output "${mock_curl}" $'{"id":"file-new"}\n200' 4

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_success
  assert_output_contains "[INFO] Started database dump push to Google Drive."
  assert_output_contains "Local file:  .data/db.sql"
  assert_output_contains "Remote file: db.sql"
  assert_output_contains "Folder ID:   folder-123"
  assert_output_contains "[ OK ] Authenticated with the service account."
  assert_output_contains "Creating a new file in the folder."
  assert_output_contains "[ OK ] Started the upload session."
  assert_output_contains "[ OK ] Uploaded the database dump file."
  assert_output_contains "[ OK ] Finished database dump push to Google Drive."
  assert_output_not_contains "test-token"
  assert_output_not_contains "session-1"

  assert_equal "4" "$(mock_get_call_num "${mock_curl}")"

  # The token request carries a signed JWT assertion for the full Drive scope.
  token_args="$(mock_get_call_args "${mock_curl}" 1)"
  assertion="${token_args##*assertion=}"
  assertion="${assertion%% *}"
  IFS='.' read -r jwt_header jwt_claims jwt_signature <<<"${assertion}"
  assert_not_empty "${jwt_signature}"
  claims="$(php -r 'print base64_decode(strtr($argv[1], "-_", "+/"));' "${jwt_claims}")"
  assert_string_contains "${claims}" '"scope":"https:\/\/www.googleapis.com\/auth\/drive"'

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 2)" "q=name = 'db.sql' and 'folder-123' in parents and trashed = false"

  session_args="$(mock_get_call_args "${mock_curl}" 3)"
  assert_string_contains "${session_args}" "-X POST"
  assert_string_contains "${session_args}" "Authorization: Bearer test-token"
  assert_string_contains "${session_args}" '--data {"name":"db.sql","parents":["folder-123"]}'
  assert_string_contains "${session_args}" "https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true"

  upload_args="$(mock_get_call_args "${mock_curl}" 4)"
  assert_string_contains "${upload_args}" "-X PUT --upload-file .data/db.sql"
  assert_string_contains "${upload_args}" "https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=session-1"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Replace the existing database dump file in place" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY=""
  VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY="$(cat "${BUILD_DIR}/gdrive.json")"
  export VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY
  # The folder falls back to the folder the database is fetched from.
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID=""
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID="fetch-folder"
  unset VORTEX_PUSH_DB_GDRIVE_DB_DIR VORTEX_PUSH_DB_GDRIVE_DB_FILE

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[{"id":"file-456"}]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 200\r\nLocation: https://www.googleapis.com/upload/drive/v3/files/file-456?uploadType=resumable&upload_id=session-2\r\n' 3
  mock_set_output "${mock_curl}" $'{"id":"file-456"}\n200' 4

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_success
  assert_output_contains "Local file:  ./.data/db.sql"
  assert_output_contains "Folder ID:   fetch-folder"
  assert_output_contains "Replacing the existing file with ID file-456."
  assert_output_contains "[ OK ] Finished database dump push to Google Drive."

  session_args="$(mock_get_call_args "${mock_curl}" 3)"
  assert_string_contains "${session_args}" "-X PATCH"
  assert_string_contains "${session_args}" "--data {}"
  assert_string_contains "${session_args}" "https://www.googleapis.com/upload/drive/v3/files/file-456?uploadType=resumable&supportsAllDrives=true"

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 4)" "upload_id=session-2"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the service account key is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY=""
  export VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY=""
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY or VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the folder ID is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="{}"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID=""
  export VORTEX_FETCH_DB_GDRIVE_FOLDER_ID=""

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Missing required value for VORTEX_PUSH_DB_GDRIVE_FOLDER_ID or VORTEX_FETCH_DB_GDRIVE_FOLDER_ID."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the database dump file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="{}"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"
  export VORTEX_PUSH_DB_GDRIVE_DB_FILE="db.sql"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Database dump file .data/db.sql does not exist."
  assert_output_not_contains "Started database dump push to Google Drive."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the service account key is invalid" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="not json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"

  mock_curl=$(mock_command "curl")

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to authenticate with the service account. Check that VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY or VORTEX_GDRIVE_SERVICE_ACCOUNT_KEY is a valid service account JSON key."
  assert_equal "0" "$(mock_get_call_num "${mock_curl}")"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Fail without creating a file when the folder cannot be listed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"error":{"code":403,"message":"Insufficient permissions."}}' 2

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to list the folder-123 folder. Check that the folder ID is correct and that the folder is shared with the service account."
  assert_output_not_contains "Creating a new file in the folder."
  assert_equal "2" "$(mock_get_call_num "${mock_curl}")"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the upload session has no location" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 403\r\ncontent-type: application/json\r\n' 3

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Unable to start the upload session: the response has no upload location. Check that the folder is in a shared drive and that the service account can edit it."
  assert_equal "3" "$(mock_get_call_num "${mock_curl}")"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the upload returns a non-2xx status" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 200\r\nlocation: https://www.googleapis.com/upload/drive/v3/files?upload_id=session-3\r\n' 3
  mock_set_output "${mock_curl}" $'{"error":{"code":403,"message":"The user does not have sufficient permissions."}}\n403' 4

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "The user does not have sufficient permissions."
  assert_output_contains "[FAIL] Unable to push to Google Drive: HTTP status 403."
  assert_output_not_contains "Finished database dump push to Google Drive."

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Fail when the upload request fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_PUSH_DB_GDRIVE_DB_DIR=".data"

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 200\r\nlocation: https://www.googleapis.com/upload/drive/v3/files?upload_id=session-4\r\n' 3
  mock_set_status "${mock_curl}" 28 4

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_failure
  assert_output_contains "[FAIL] Google Drive upload request failed with curl exit 28."

  rm -rf .data

  popd >/dev/null
}

@test "push-db-gdrive: Push the database dump file the database is fetched with" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db_fetched.sql
  fixture_gdrive_key

  export VORTEX_PUSH_DB_GDRIVE_SERVICE_ACCOUNT_KEY="${BUILD_DIR}/gdrive.json"
  export VORTEX_PUSH_DB_GDRIVE_FOLDER_ID="folder-123"
  export VORTEX_FETCH_DB_FILE="db_fetched.sql"
  unset VORTEX_PUSH_DB_GDRIVE_DB_DIR VORTEX_PUSH_DB_GDRIVE_DB_FILE

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"access_token":"test-token"}' 1
  mock_set_output "${mock_curl}" '{"files":[]}' 2
  mock_set_output "${mock_curl}" $'HTTP/2 200\r\nlocation: https://www.googleapis.com/upload/drive/v3/files?upload_id=session-5\r\n' 3
  mock_set_output "${mock_curl}" $'{"id":"file-new"}\n200' 4

  run .vortex/tooling/src/vortex-push-db-gdrive
  assert_success
  assert_output_contains "Local file:  ./.data/db_fetched.sql"
  assert_output_contains "Remote file: db_fetched.sql"

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 2)" "q=name = 'db_fetched.sql' and"
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 4)" "--upload-file ./.data/db_fetched.sql"

  rm -rf .data

  popd >/dev/null
}
