#!/usr/bin/env bats
##
# Unit tests for the 'vortex-push-db-s3' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "push-db-s3: Push database file successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "CREATE TABLE test (id INT);" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Local file:     .data/db.sql"
    "Remote file:    db.sql"
    "S3 bucket:      test-bucket"
    "S3 region:      ap-southeast-2"
    "Storage class:  STANDARD"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create file at default location.
  mkdir -p ./.data
  echo "database content" >./.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Local file:     ./.data/db.sql"
    "Remote file:    db.sql"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  # Don't set VORTEX_PUSH_DB_S3_DB_DIR and VORTEX_PUSH_DB_S3_DB_FILE to test defaults.
  unset VORTEX_PUSH_DB_S3_DB_DIR VORTEX_PUSH_DB_S3_DB_FILE

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Use shortcut variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "S3 bucket:      shortcut-bucket"
    "S3 region:      us-east-1"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear VORTEX_* variables so shortcut fallback kicks in.
  export VORTEX_PUSH_DB_S3_ACCESS_KEY=""
  export VORTEX_PUSH_DB_S3_SECRET_KEY=""
  export VORTEX_PUSH_DB_S3_BUCKET=""
  export VORTEX_PUSH_DB_S3_REGION=""
  # Set shortcut variables instead of VORTEX_PUSH_DB_S3_* variables.
  export S3_ACCESS_KEY="shortcut-access-key"
  export S3_SECRET_KEY="shortcut-secret-key"
  export S3_BUCKET="shortcut-bucket"
  export S3_REGION="us-east-1"
  # Clear prefix shortcut to prevent environment leakage.
  unset S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Use custom remote file name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Remote file:    backup/db_latest.sql"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"
  export VORTEX_PUSH_DB_S3_REMOTE_FILE="backup/db_latest.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Use custom prefix" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Remote file:    backups/daily/db.sql"
    "S3 prefix:      backups/daily/"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_PREFIX="backups/daily/"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Normalize prefix without trailing slash" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Remote file:    backups/daily/db.sql"
    "S3 prefix:      backups/daily/"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  # Prefix without trailing slash should be normalized.
  export VORTEX_PUSH_DB_S3_PREFIX="backups/daily"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Fail when S3 push returns non-2xx status" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "CREATE TABLE test (id INT);" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "@curl * # 0 # AccessDenied\n403"

    "[FAIL] Unable to push to S3: HTTP status 403."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Fail when VORTEX_PUSH_DB_S3_ACCESS_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_PUSH_DB_S3_ACCESS_KEY."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY=""
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "push-db-s3: Fail when VORTEX_PUSH_DB_S3_SECRET_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_PUSH_DB_S3_SECRET_KEY."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY=""
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "push-db-s3: Fail when VORTEX_PUSH_DB_S3_BUCKET is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_PUSH_DB_S3_BUCKET."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET=""
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "push-db-s3: Fail when VORTEX_PUSH_DB_S3_REGION is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_PUSH_DB_S3_REGION."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION=""

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "push-db-s3: Fail when database dump file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Do not create the file to trigger the error.
  rm -rf .data

  declare -a STEPS=(
    "[FAIL] Database dump file .data/db.sql does not exist."
    "- [ OK ] Finished database dump push to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null
}

@test "push-db-s3: Use the database fetch values" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db2.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Local file:     .data/db2.sql"
    "Remote file:    store/db2.sql"
    "S3 bucket:      fetch-bucket"
    "S3 region:      eu-west-1"
    "S3 prefix:      store/"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_PUSH_DB_S3_ACCESS_KEY=""
  export VORTEX_PUSH_DB_S3_SECRET_KEY=""
  export VORTEX_PUSH_DB_S3_BUCKET=""
  export VORTEX_PUSH_DB_S3_REGION=""
  export VORTEX_PUSH_DB_S3_PREFIX=""
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="fetch-access-key"
  export VORTEX_FETCH_DB_S3_SECRET_KEY="fetch-secret-key"
  export VORTEX_FETCH_DB_S3_BUCKET="fetch-bucket"
  export VORTEX_FETCH_DB_S3_REGION="eu-west-1"
  export VORTEX_FETCH_DB_S3_PREFIX="store"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  # The remote file name follows the dump file name.
  export VORTEX_PUSH_DB_S3_DB_FILE="db2.sql"
  unset VORTEX_PUSH_DB_S3_REMOTE_FILE

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Push the database dump file the database is fetched with" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db_fetched.sql

  declare -a STEPS=(
    "[INFO] Started database dump push to S3."

    "Local file:     ./.data/db_fetched.sql"
    "Remote file:    db_fetched.sql"

    "@curl * # 0 # 200"

    "[ OK ] Finished database dump push to S3."
  )

  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_FETCH_DB_FILE="db_fetched.sql"
  unset VORTEX_PUSH_DB_S3_DB_DIR VORTEX_PUSH_DB_S3_DB_FILE VORTEX_PUSH_DB_S3_REMOTE_FILE

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-push-db-s3
  steps_run "assert" "${mocks[@]}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

# Exports the variables of a push to the 'test-bucket' bucket with the
# 'backups/' prefix.
setup_s3_push() {
  mkdir -p .data
  echo "database content" >.data/db.sql

  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_FETCH_DB_S3_ACCESS_KEY="" VORTEX_FETCH_DB_S3_SECRET_KEY="" VORTEX_FETCH_DB_S3_BUCKET="" VORTEX_FETCH_DB_S3_REGION="" VORTEX_FETCH_DB_S3_PREFIX=""

  export VORTEX_PUSH_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_PUSH_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_PUSH_DB_S3_BUCKET="test-bucket"
  export VORTEX_PUSH_DB_S3_REGION="ap-southeast-2"
  export VORTEX_PUSH_DB_S3_PREFIX="backups"
  export VORTEX_PUSH_DB_S3_DB_DIR=".data"
  export VORTEX_PUSH_DB_S3_DB_FILE="db.sql"
  unset VORTEX_PUSH_DB_S3_REMOTE_FILE VORTEX_PUSH_DB_FILE_SUFFIX VORTEX_PUSH_DB_TIMESTAMPED_COPY
}

@test "push-db-s3: Push only the latest dump by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_s3_push

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  run .vortex/tooling/src/vortex-push-db-s3
  assert_success
  assert_output_contains "Remote file:    backups/db.sql"
  assert_output_contains "[ OK ] Uploaded the database dump file."
  assert_output_not_contains "Copying the database dump file"
  assert_output_contains "[ OK ] Finished database dump push to S3."

  assert_equal "1" "$(mock_get_call_num "${mock_curl}")"

  upload_args="$(mock_get_call_args "${mock_curl}" 1)"
  assert_string_contains "${upload_args}" "--upload-file .data/db.sql"
  assert_string_contains "${upload_args}" "https://test-bucket.s3.ap-southeast-2.amazonaws.com/backups/db.sql"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Copy the upload to a timestamped copy when enabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_s3_push
  export VORTEX_PUSH_DB_TIMESTAMPED_COPY=1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1
  mock_set_output "${mock_curl}" $'<CopyObjectResult></CopyObjectResult>\n200' 2

  run .vortex/tooling/src/vortex-push-db-s3
  assert_success
  assert_output_contains "[ OK ] Uploaded the database dump file."
  assert_output_contains "[ OK ] Copied the database dump file to backups/db_"
  assert_output_contains "[ OK ] Finished database dump push to S3."

  assert_equal "2" "$(mock_get_call_num "${mock_curl}")"

  # The copy runs after the upload, names the latest dump as its source and
  # sends no body.
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 1)" "https://test-bucket.s3.ap-southeast-2.amazonaws.com/backups/db.sql"
  copy_args="$(mock_get_call_args "${mock_curl}" 2)"
  assert_string_contains "${copy_args}" "--header x-amz-copy-source: /test-bucket/backups/db.sql"
  assert_string_not_contains "${copy_args}" "--upload-file"
  [[ ${copy_args} =~ https://test-bucket\.s3\.ap-southeast-2\.amazonaws\.com/backups/db_[0-9]{8}-[0-9]{6}\.sql ]]

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Fail when the timestamped copy cannot be made" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_s3_push
  export VORTEX_PUSH_DB_TIMESTAMPED_COPY=1

  # A copy error can come with a 200 status. Each run makes 2 requests.
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1
  mock_set_output "${mock_curl}" $'<Error><Code>InternalError</Code></Error>\n200' 2
  mock_set_output "${mock_curl}" "200" 3
  mock_set_output "${mock_curl}" $'<Error><Code>AccessDenied</Code></Error>\n403' 4

  for code in 200 403; do
    run .vortex/tooling/src/vortex-push-db-s3
    assert_failure
    assert_output_contains "[ OK ] Uploaded the database dump file."
    assert_output_contains "Response: <Error><Code>"
    assert_output_contains "[FAIL] Unable to copy the database dump file to the timestamped copy: HTTP status ${code}."
    assert_output_not_contains "Finished database dump push to S3."
  done

  assert_equal "4" "$(mock_get_call_num "${mock_curl}")"

  rm -rf .data

  popd >/dev/null
}

@test "push-db-s3: Push an indexed database with a file name suffix" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "migration content" >.data/db2.sql

  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX
  export VORTEX_DB_INDEX=2
  export VORTEX_FETCH_DB2_S3_ACCESS_KEY="db2-access-key"
  export VORTEX_FETCH_DB2_S3_SECRET_KEY="db2-secret-key"
  export VORTEX_FETCH_DB2_S3_BUCKET="db2-bucket"
  export VORTEX_FETCH_DB2_S3_REGION="eu-west-1"
  export VORTEX_FETCH_DB2_S3_PREFIX=""
  export VORTEX_FETCH_DB2_FILE="db2.sql"
  export VORTEX_PUSH_DB_FILE_SUFFIX="_circleci"
  export VORTEX_PUSH_DB_TIMESTAMPED_COPY=1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1
  mock_set_output "${mock_curl}" $'<CopyObjectResult></CopyObjectResult>\n200' 2

  run .vortex/tooling/src/vortex-push-db-s3
  assert_success
  assert_output_contains "[INFO] Started database 2 dump push to S3."
  assert_output_contains "Local file:     ./.data/db2.sql"
  assert_output_contains "Remote file:    db2_circleci.sql"
  assert_output_contains "S3 bucket:      db2-bucket"
  assert_output_contains "[ OK ] Copied the database dump file to db2_circleci_"
  assert_output_contains "[ OK ] Finished database 2 dump push to S3."

  assert_string_contains "$(mock_get_call_args "${mock_curl}" 1)" "https://db2-bucket.s3.eu-west-1.amazonaws.com/db2_circleci.sql"
  assert_string_contains "$(mock_get_call_args "${mock_curl}" 2)" "--header x-amz-copy-source: /db2-bucket/db2_circleci.sql"

  rm -rf .data

  popd >/dev/null
}
