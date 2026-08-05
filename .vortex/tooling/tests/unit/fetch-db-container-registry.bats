#!/usr/bin/env bats
##
# Unit tests for fetch-db-container-registry.sh
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "fetch-db-container-registry: Fetch image successfully when not found on host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The image is absent from the host, so the inspect is followed by a registry
  # login and a pull. The login script is invoked by its own path rather than
  # through PATH, so it runs for real and its own 'docker login' is call 2.
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Expand archived image when db.tar exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create mock archive file
  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  # First call to image inspect fails (not on host), load succeeds, second inspect succeeds (after load)
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/myapp'" 2
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Found archived database container image file .data/db.tar. Expanding..."
  assert_output_contains "Found expanded myorg/myapp image on host."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  # Clean up
  rm -f .data/db.tar

  popd >/dev/null
}

@test "fetch-db-container-registry: Use base image when archive not found and base image provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled base image'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE_BASE="myorg/base"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Database container image was not found. Using base image myorg/base."
  assert_output_contains "Fetching myorg/base image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fetch image when it exists on host and no archive exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # The image is found on the host, but the fetch is not skipped: without an
  # expanded archive the script still logs in and pulls.
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Found myorg/myapp image on host."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Use default registry when not specified" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled from docker.io'" 3

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  # Don't set VORTEX_FETCH_DB_CONTAINER_REGISTRY to test default
  unset VORTEX_FETCH_DB_CONTAINER_REGISTRY VORTEX_CONTAINER_REGISTRY
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Fetching myorg/myapp image from the registry."
  # The registry reaches the pull target but never the output, so only the
  # recorded arguments show that it defaulted to docker.io.
  assert_string_contains "$(mock_get_call_args "${mock_docker}" 3)" "pull docker.io/myorg/myapp"
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Resolve indexed variables with VORTEX_DB_INDEX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'logged in'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3

  # Set database index as used in CI: VORTEX_DB_INDEX=2.
  export VORTEX_DB_INDEX="2"

  # Set the shorthand image variable with index.
  export VORTEX_DB2_IMAGE="myorg/migration-db"

  # Set remaining required variables with index in the DB part.
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_FETCH_DB2_CONTAINER_REGISTRY_DB_DIR=".data"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_success
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "Fetching myorg/migration-db image from the registry."
  assert_output_contains "[ OK ] Finished database data container image fetch."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_USER
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER or VORTEX_CONTAINER_REGISTRY_USER."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE="myorg/myapp"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_PASS

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Missing required value for VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS or VORTEX_CONTAINER_REGISTRY_PASS."

  popd >/dev/null
}

@test "fetch-db-container-registry: Fail when VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE=""
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_FETCH_DB_CONTAINER_REGISTRY_PASS="testpass"

  run .vortex/tooling/src/vortex-fetch-db-container-registry
  assert_failure
  assert_output_contains "[INFO] Started database data container image fetch."
  assert_output_contains "[FAIL] Destination image name is not specified. Please provide VORTEX_FETCH_DB_CONTAINER_REGISTRY_IMAGE or VORTEX_DB_IMAGE in a format <org>/<repository>."

  popd >/dev/null
}
