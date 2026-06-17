#!/usr/bin/env bats
##
# Unit tests for fetch-db.sh
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

@test "fetch-db: Download with URL source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the fetch-db-url.sh script by creating it
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Downloading database dump file."
echo "Finished database dump download from URL."
EOF
  chmod +x .vortex/tooling/src/fetch-db-url

  # Mock ls command for final directory listing
  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 1

  # Mock touch command for semaphore file test
  mock_touch=$(mock_command "touch")
  mock_set_output "${mock_touch}" "" 1

  export VORTEX_FETCH_DB_SOURCE="url"
  export VORTEX_FETCH_DB_PROCEED="1"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"
  export VORTEX_FETCH_DB_SEMAPHORE=".data/.db-downloaded"

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Downloading database dump file."
  assert_output_contains "Finished database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}

@test "fetch-db: Skip when disabled and use default source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Test skipping when VORTEX_FETCH_DB_PROCEED is not 1
  export VORTEX_FETCH_DB_PROCEED="0"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Skipping database download as DB_DOWNLOAD_PROCEED is not set to 1."

  # Test default source (should default to url/curl)
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x .vortex/tooling/src/fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 1

  # Unset VORTEX_FETCH_DB_SOURCE to test default
  unset VORTEX_FETCH_DB_SOURCE
  export VORTEX_FETCH_DB_PROCEED="1"

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}

@test "fetch-db: Container registry source skips file existence check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create existing database files that would normally trigger the cache check.
  mkdir -p .data
  touch .data/db.sql
  touch .data/db.tar

  # Mock the container registry sub-script.
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-container-registry <<'EOF'
#!/usr/bin/env bash
echo "Started database data container image download."
echo "Finished database data container image download."
EOF
  chmod +x .vortex/tooling/src/fetch-db-container-registry

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  export VORTEX_FETCH_DB_SOURCE="container_registry"
  export VORTEX_FETCH_DB_PROCEED="1"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"
  export VORTEX_FETCH_DB_FORCE=""

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Started database data container image download."
  assert_output_contains "Finished database data container image download."
  assert_output_contains "Finished database download."
  assert_output_not_contains "Found existing database dump file(s)."
  assert_output_not_contains "Using existing database dump file(s)."
  assert_output_not_contains "Download will not proceed."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed long-form variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the URL sub-script.
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x .vortex/tooling/src/fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  # Set database index to pick up long-form indexed variables.
  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_PROCEED="1"
  export VORTEX_FETCH_DB2_DIR=".data"
  export VORTEX_FETCH_DB2_FILE="db2.sql"

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database 2 download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database 2 download."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed shorthand variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the URL sub-script.
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x .vortex/tooling/src/fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  # Set database index to pick up shorthand indexed variables.
  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_PROCEED="1"
  # Use shorthand forms for DIR and FILE.
  export VORTEX_DB2_DIR=".data"
  export VORTEX_DB2_FILE="db2.sql"

  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database 2 download."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database 2 download."

  popd >/dev/null
}

@test "fetch-db: Existing database file handling" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create actual directory and file structure to simulate existing DB
  mkdir -p .data
  touch .data/db.sql

  # Mock ls command for listing existing files
  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 2

  export VORTEX_FETCH_DB_SOURCE="url"
  export VORTEX_FETCH_DB_PROCEED="1"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"

  # Test using existing file when force is not set
  export VORTEX_FETCH_DB_FORCE=""
  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Using existing database dump file(s)."
  assert_output_contains "Download will not proceed."
  assert_output_contains "Remove existing database file or set VORTEX_FETCH_DB_FORCE value to 1 to force download."

  # Test forcing download when existing file found
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump download from URL."
echo "Finished database dump download from URL."
EOF
  chmod +x .vortex/tooling/src/fetch-db-url

  export VORTEX_FETCH_DB_FORCE="1"
  run .vortex/tooling/src/fetch-db
  assert_success
  assert_output_contains "Started database download."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Will download a fresh copy of the database."
  assert_output_contains "Started database dump download from URL."
  assert_output_contains "Finished database download."

  popd >/dev/null
}
