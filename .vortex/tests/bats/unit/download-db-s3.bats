#!/usr/bin/env bats
##
# Unit tests for download-db-s3.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "download-db-s3: Download database file successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    "[INFO] Started database dump download from S3."

    "Remote file: db.sql"
    "Local path:  .data/db.sql"
    "S3 bucket:   test-bucket"
    "S3 region:   ap-southeast-2"

    '@curl * # 0 #  # echo "CREATE TABLE test (id INT);" > .data/db.sql'

    "[ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "CREATE TABLE test"

  rm -rf .data

  popd >/dev/null
}

@test "download-db-s3: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    "[INFO] Started database dump download from S3."

    "Remote file: db.sql"
    "Local path:  ./.data/db.sql"
    "S3 bucket:   test-bucket"
    "S3 region:   ap-southeast-2"

    '@curl * # 0 #  # echo "database content" > ./.data/db.sql'

    "[ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"
  # Don't set VORTEX_DB_DIR and VORTEX_DB_FILE to test defaults.
  unset VORTEX_DB_DIR VORTEX_DB_FILE

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "download-db-s3: Use shortcut variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    "[INFO] Started database dump download from S3."

    "S3 bucket:   shortcut-bucket"
    "S3 region:   us-east-1"

    '@curl * # 0 #  # echo "database content" > .data/db.sql'

    "[ OK ] Finished database dump download from S3."
  )

  # Clear VORTEX_* variables so shortcut fallback kicks in.
  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY=""
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY=""
  export VORTEX_DOWNLOAD_DB_S3_BUCKET=""
  export VORTEX_DOWNLOAD_DB_S3_REGION=""
  # Set shortcut variables instead of VORTEX_DOWNLOAD_DB_S3_* variables.
  export S3_ACCESS_KEY="shortcut-access-key"
  export S3_SECRET_KEY="shortcut-secret-key"
  export S3_BUCKET="shortcut-bucket"
  export S3_REGION="us-east-1"
  # Clear prefix shortcut to prevent environment leakage.
  unset S3_PREFIX
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "download-db-s3: Use custom prefix" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    "[INFO] Started database dump download from S3."

    "Remote file: backups/daily/db.sql"
    "S3 prefix:   backups/daily/"

    '@curl * # 0 #  # echo "database content" > .data/db.sql'

    "[ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"
  export VORTEX_DOWNLOAD_DB_S3_PREFIX="backups/daily/"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "download-db-s3: Normalize prefix without trailing slash" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    "[INFO] Started database dump download from S3."

    "Remote file: backups/daily/db.sql"
    "S3 prefix:   backups/daily/"

    '@curl * # 0 #  # echo "database content" > .data/db.sql'

    "[ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"
  # Prefix without trailing slash should be normalized.
  export VORTEX_DOWNLOAD_DB_S3_PREFIX="backups/daily"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_success

  rm -rf .data

  popd >/dev/null
}

@test "download-db-s3: Fail when VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY."
    "- [ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY=""
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-s3: Fail when VORTEX_DOWNLOAD_DB_S3_SECRET_KEY is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_S3_SECRET_KEY."
    "- [ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY=""
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-s3: Fail when VORTEX_DOWNLOAD_DB_S3_BUCKET is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_S3_BUCKET."
    "- [ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET=""
  export VORTEX_DOWNLOAD_DB_S3_REGION="ap-southeast-2"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-s3: Fail when VORTEX_DOWNLOAD_DB_S3_REGION is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_S3_REGION."
    "- [ OK ] Finished database dump download from S3."
  )

  # Clear shortcut variables to prevent environment leakage.
  unset S3_ACCESS_KEY S3_SECRET_KEY S3_BUCKET S3_REGION S3_PREFIX

  export VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY="testaccesskey"
  export VORTEX_DOWNLOAD_DB_S3_SECRET_KEY="testsecretkey"
  export VORTEX_DOWNLOAD_DB_S3_BUCKET="test-bucket"
  export VORTEX_DOWNLOAD_DB_S3_REGION=""

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-s3.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}
