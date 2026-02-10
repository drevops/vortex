#!/usr/bin/env bats
##
# Unit tests for upload-db-s3.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "upload-db-s3: Upload database file successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "CREATE TABLE test (id INT);" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "Local file:     .data/db.sql"
    "Remote file:    db.sql"
    "S3 bucket:      test-bucket"
    "S3 region:      ap-southeast-2"
    "Storage class:  STANDARD"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create file at default location.
  mkdir -p ./.data
  echo "database content" >./.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "Local file:     ./.data/db.sql"
    "Remote file:    db.sql"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  # Don't set VORTEX_UPLOAD_DB_S3_DB_DIR and VORTEX_UPLOAD_DB_S3_DB_FILE to test defaults.
  unset VORTEX_UPLOAD_DB_S3_DB_DIR VORTEX_UPLOAD_DB_S3_DB_FILE

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Use shortcut variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "S3 bucket:      shortcut-bucket"
    "S3 region:      us-east-1"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear VORTEX_* variables so shortcut fallback kicks in.
  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY=""
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY=""
  export VORTEX_UPLOAD_DB_S3_BUCKET=""
  export VORTEX_UPLOAD_DB_S3_REGION=""
  # Set shortcut variables instead of VORTEX_UPLOAD_DB_S3_* variables.
  export S3_ACCESS_KEY="shortcut-access-key"
  export S3_SECRET_KEY="shortcut-secret-key"
  export S3_BUCKET="shortcut-bucket"
  export S3_REGION="us-east-1"
  # Clear prefix shortcut to prevent environment leakage.
  unset S3_PREFIX
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Use custom remote file name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "Remote file:    backup/db_latest.sql"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"
  export VORTEX_UPLOAD_DB_S3_REMOTE_FILE="backup/db_latest.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Use custom prefix" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "Remote file:    backups/daily/db.sql"
    "S3 prefix:      backups/daily/"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_UPLOAD_DB_S3_PREFIX="backups/daily/"
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Normalize prefix without trailing slash" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .data
  echo "database content" >.data/db.sql

  declare -a STEPS=(
    "[INFO] Started database dump upload to S3."

    "Remote file:    backups/daily/db.sql"
    "S3 prefix:      backups/daily/"

    "@curl * # 0"

    "[ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  # Prefix without trailing slash should be normalized.
  export VORTEX_UPLOAD_DB_S3_PREFIX="backups/daily"
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "upload-db-s3: Fail when VORTEX_UPLOAD_DB_S3_ACCESS_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_UPLOAD_DB_S3_ACCESS_KEY."
    "- [ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY=""
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "upload-db-s3: Fail when VORTEX_UPLOAD_DB_S3_SECRET_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_UPLOAD_DB_S3_SECRET_KEY."
    "- [ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY=""
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "upload-db-s3: Fail when VORTEX_UPLOAD_DB_S3_BUCKET is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_UPLOAD_DB_S3_BUCKET."
    "- [ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET=""
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "upload-db-s3: Fail when VORTEX_UPLOAD_DB_S3_REGION is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_UPLOAD_DB_S3_REGION."
    "- [ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION=""

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "upload-db-s3: Fail when database dump file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Do not create the file to trigger the error.
  rm -rf .data

  declare -a STEPS=(
    "[FAIL] Database dump file .data/db.sql does not exist."
    "- [ OK ] Finished database dump upload to S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_UPLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_UPLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_UPLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_UPLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_UPLOAD_DB_S3_DB_DIR=".data"
  export VORTEX_UPLOAD_DB_S3_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/upload-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}
