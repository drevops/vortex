#!/usr/bin/env bats
##
# Unit tests for fetch-db.sh
#
# shellcheck disable=SC2030,SC2031,SC2016

load ../_helper.bash

@test "fetch-db: Fetch with URL source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the fetch-db-url.sh script by creating it
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/vortex-fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump fetch from URL."
echo "Fetching database dump file."
echo "Finished database dump fetch from URL."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-url

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

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Started database dump fetch from URL."
  assert_output_contains "Fetching database dump file."
  assert_output_contains "Finished database dump fetch from URL."
  assert_output_contains "Finished database fetch."

  popd >/dev/null
}

@test "fetch-db: Skip when disabled and use default source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Test skipping when VORTEX_FETCH_DB_PROCEED is not 1
  export VORTEX_FETCH_DB_PROCEED="0"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Skipped database fetch as VORTEX_FETCH_DB_PROCEED is not set to 1."

  # Test default source (should default to url/curl)
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/vortex-fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump fetch from URL."
echo "Finished database dump fetch from URL."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 1024 -rw-r--r-- 1 user user 1048576 Jan 01 12:00 db.sql" 1

  # Unset VORTEX_FETCH_DB_SOURCE to test default
  unset VORTEX_FETCH_DB_SOURCE
  export VORTEX_FETCH_DB_PROCEED="1"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Started database dump fetch from URL."
  assert_output_contains "Finished database fetch."

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
  cat >.vortex/tooling/src/vortex-fetch-db-container-registry <<'EOF'
#!/usr/bin/env bash
echo "Started database data container image fetch."
echo "Finished database data container image fetch."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-container-registry

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  export VORTEX_FETCH_DB_SOURCE="container_registry"
  export VORTEX_FETCH_DB_PROCEED="1"
  export VORTEX_FETCH_DB_DIR=".data"
  export VORTEX_FETCH_DB_FILE="db.sql"
  export VORTEX_FETCH_DB_FORCE=""

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Started database data container image fetch."
  assert_output_contains "Finished database data container image fetch."
  assert_output_contains "Finished database fetch."
  assert_output_not_contains "Found existing database dump file(s)."
  assert_output_not_contains "Using existing database dump file(s)."
  assert_output_not_contains "Fetch will not proceed."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed long-form variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the URL sub-script.
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/vortex-fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump fetch from URL."
echo "Finished database dump fetch from URL."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  # Set database index to pick up long-form indexed variables.
  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_PROCEED="1"
  export VORTEX_FETCH_DB2_DIR=".data"
  export VORTEX_FETCH_DB2_FILE="db2.sql"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database 2 fetch."
  assert_output_contains "Started database dump fetch from URL."
  assert_output_contains "Finished database 2 fetch."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed shorthand variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Mock the URL sub-script.
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/vortex-fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump fetch from URL."
echo "Finished database dump fetch from URL."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-url

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  # Set database index to pick up shorthand indexed variables.
  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_PROCEED="1"
  # Use shorthand forms for DIR and FILE.
  export VORTEX_DB2_DIR=".data"
  export VORTEX_DB2_FILE="db2.sql"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database 2 fetch."
  assert_output_contains "Started database dump fetch from URL."
  assert_output_contains "Finished database 2 fetch."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed dir from plain VORTEX_DB_DIR fallback" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # The migration DB (index 2) has a per-index file name (db2.sql) but no
  # per-index directory, so the non-indexed VORTEX_DB_DIR must act as the base
  # directory. An existing dump placed there should be discovered.
  mkdir -p custom_data
  touch custom_data/db2.sql

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  export VORTEX_DB_INDEX="2"
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_PROCEED="1"
  export VORTEX_DB_DIR="custom_data"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  # The router resolved the indexed dump directory to custom_data via the
  # plain VORTEX_DB_DIR fallback and found the existing dump there.
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Using existing database dump file(s)."

  popd >/dev/null
}

@test "fetch-db: Resolve indexed file name from plain VORTEX_DB_FILE fallback" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # An index with no per-index file override (unlike index 2, which the fixture
  # sets to db2.sql) must fall back to the non-indexed VORTEX_DB_FILE for the
  # dump file name. An existing dump with that name should be discovered.
  mkdir -p .data
  touch .data/custom.sql

  mock_ls=$(mock_command "ls")
  mock_set_output "${mock_ls}" "total 0" 1

  export VORTEX_DB_INDEX="3"
  export VORTEX_FETCH_DB3_SOURCE="url"
  export VORTEX_FETCH_DB3_PROCEED="1"
  export VORTEX_DB_FILE="custom.sql"

  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  # The router resolved the indexed dump file name to custom.sql via the
  # plain VORTEX_DB_FILE fallback and found the existing dump.
  assert_output_contains "Found existing database dump file(s)."

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
  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Using existing database dump file(s)."
  assert_output_contains "Fetch will not proceed."
  assert_output_contains "Remove existing database file or set VORTEX_FETCH_DB_FORCE value to 1 to force fetch."

  # Test forcing download when existing file found
  mkdir -p .vortex/tooling/src
  cat >.vortex/tooling/src/vortex-fetch-db-url <<'EOF'
#!/usr/bin/env bash
echo "Started database dump fetch from URL."
echo "Finished database dump fetch from URL."
EOF
  chmod +x .vortex/tooling/src/vortex-fetch-db-url

  export VORTEX_FETCH_DB_FORCE="1"
  run .vortex/tooling/src/vortex-fetch-db
  assert_success
  assert_output_contains "Started database fetch."
  assert_output_contains "Found existing database dump file(s)."
  assert_output_contains "Will fetch a fresh copy of the database."
  assert_output_contains "Started database dump fetch from URL."
  assert_output_contains "Finished database fetch."

  popd >/dev/null
}
