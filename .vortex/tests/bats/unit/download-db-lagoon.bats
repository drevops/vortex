#!/usr/bin/env bats
#
# Unit tests for download-db-lagoon.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "download-db-lagoon: Download database successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Clean up any existing test files first to force full download workflow
  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Lagoon."

    # Mock SSH setup script call
    "[INFO] Started SSH setup."

    "- Database dump refresh requested. Will create a new dump."

    # Mock SSH command to create/check database dump on remote
    "@ssh * # 0 # > Creating a database dump /tmp/db_$(date +%Y%m%d).sql."

    # Mock rsync download command with side effect to create database file
    "Downloading a database dump."
    '@rsync * # 0 #  # echo "CREATE TABLE test (id INT);" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Lagoon."
  )

  export VORTEX_DOWNLOAD_DB_LAGOON_PROJECT="testproject"
  export VORTEX_DOWNLOAD_DB_ENVIRONMENT="main"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_FILE="db.sql"

  fixture_ssh_key_prepare
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_DOWNLOAD_DB_SSH_FILE=false

  mocks="$(run_steps "setup")"

  run scripts/vortex/download-db-lagoon.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Verify the final database file exists and has content
  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "CREATE TABLE test"

  # Clean up
  rm -rf .data

  popd >/dev/null
}

@test "download-db-lagoon: Use existing dump when available" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Clean up any existing test files first
  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Lagoon."

    # Mock SSH setup script call
    "[INFO] Started SSH setup."

    "- Database dump refresh requested. Will create a new dump."

    # Mock SSH command that finds existing dump
    "@ssh * # 0 # > Using existing dump /tmp/db_$(date +%Y%m%d).sql."

    # Mock rsync download command with side effect to create database file
    "Downloading a database dump."
    '@rsync * # 0 #  # echo "existing database content" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Lagoon."
  )

  export VORTEX_DOWNLOAD_DB_LAGOON_PROJECT="testproject"
  export VORTEX_DOWNLOAD_DB_ENVIRONMENT="main"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_FILE="db.sql"

  fixture_ssh_key_prepare
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_DOWNLOAD_DB_SSH_FILE=false

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-lagoon.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Verify the final database file exists and has content
  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "existing database content"

  # Clean up
  rm -rf .data

  popd >/dev/null
}

@test "download-db-lagoon: Refresh existing dump when requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Clean up any existing test files first
  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Lagoon."

    # Mock SSH setup script call
    "[INFO] Started SSH setup."

    "Database dump refresh requested. Will create a new dump."

    # Mock SSH command that refreshes dump (removes old, creates new)
    "@ssh * # 0 # Removed previously created DB dumps.\\n      > Creating a database dump /tmp/db_$(date +%Y%m%d).sql."

    # Mock rsync download command with side effect to create database file
    "Downloading a database dump."
    '@rsync * # 0 #  # echo "refreshed database content" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Lagoon."
  )

  export VORTEX_DOWNLOAD_DB_LAGOON_PROJECT="testproject"
  export VORTEX_DOWNLOAD_DB_ENVIRONMENT="main"
  export VORTEX_DOWNLOAD_DB_FRESH="1"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_DIR=".data"
  export VORTEX_DOWNLOAD_DB_LAGOON_DB_FILE="db.sql"

  fixture_ssh_key_prepare
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_DOWNLOAD_DB_SSH_FILE=false

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-lagoon.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Verify the final database file exists and has content
  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "refreshed database content"

  # Clean up
  rm -rf .data

  popd >/dev/null
}
