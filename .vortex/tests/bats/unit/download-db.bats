#!/usr/bin/env bats
##
# Unit tests for download-db.sh
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

@test "download-db: Download with URL source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the download-db-url.sh script by creating it
  mkdir -p scripts/vortex
  cat >scripts/vortex/download-db-url.sh <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Downloading database dump file."
echo "Finished database dump download from URL."
EOF
  chmod +x scripts/vortex/download-db-url.sh

  # Mock ls command for final directory listing
  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 1

  # Mock touch command for semaphore file test
  mock_touch=$(mock_command "touch")
  mock_set_output "${mock_touch}" "" 1

  export VORTEX_DB_DOWNLOAD_SOURCE="url"
  export VORTEX_DB_DOWNLOAD_PROCEED="1"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_SEMAPHORE=".data/.db-downloaded"

  run scripts/vortex/download-db.sh
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Downloading database dump file."
  assert_output_contains "Finished database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}

@test "download-db: Skip when disabled and use default source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Test skipping when VORTEX_DB_DOWNLOAD_PROCEED is not 1
  export VORTEX_DB_DOWNLOAD_PROCEED="0"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  run scripts/vortex/download-db.sh
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1."

  # Test default source (should default to url/curl)
  mkdir -p scripts/vortex
  cat >scripts/vortex/download-db-url.sh <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x scripts/vortex/download-db-url.sh

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 1

  # Unset VORTEX_DB_DOWNLOAD_SOURCE to test default
  unset VORTEX_DB_DOWNLOAD_SOURCE
  export VORTEX_DB_DOWNLOAD_PROCEED="1"

  run scripts/vortex/download-db.sh
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}

@test "download-db: Existing database file handling" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create actual directory and file structure to simulate existing DB
  mkdir -p .data
  touch .data/db.sql

  # Mock ls command for listing existing files
  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 2

  export VORTEX_DB_DOWNLOAD_SOURCE="url"
  export VORTEX_DB_DOWNLOAD_PROCEED="1"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  # Test using existing file when force is not set
  export VORTEX_DB_DOWNLOAD_FORCE=""
  run scripts/vortex/download-db.sh
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Using existing database dump file(s)."
  assert_output_contains "Download will not proceed."
  assert_output_contains "Remove existing database file or set VORTEX_DB_DOWNLOAD_FORCE value to 1 to force download."

  # Test forcing download when existing file found
  mkdir -p scripts/vortex
  cat >scripts/vortex/download-db-url.sh <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x scripts/vortex/download-db-url.sh

  export VORTEX_DB_DOWNLOAD_FORCE="1"
  run scripts/vortex/download-db.sh
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Will download a fresh copy of the database."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}
