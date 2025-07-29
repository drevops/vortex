#!/usr/bin/env bats

load ../_helper.bash

@test "download-db-container-registry: Download image successfully when not found on host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # First call to image inspect fails (image not found), second succeeds (after load/pull)
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'image loaded'" 2
  mock_set_side_effect "${mock_docker}" "echo 'pulled image'" 3

  # Mock the login script
  mock_set_side_effect "$(mock_command "./scripts/vortex/login-container-registry.sh")" "echo 'logged in'" 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_DB_DIR=".data"

  run scripts/vortex/download-db-container-registry.sh
  assert_success
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Downloading myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image download."

  popd >/dev/null
}

@test "download-db-container-registry: Expand archived image when db.tar exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create mock archive file
  mkdir -p .data
  touch .data/db.tar

  mock_docker=$(mock_command "docker")
  # First call to image inspect fails (not on host), load succeeds, second inspect succeeds (after load)
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'Loaded image: myorg/myapp'" 2
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 3

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_DB_DIR=".data"

  run scripts/vortex/download-db-container-registry.sh
  assert_success
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "Not found myorg/myapp image on host."
  assert_output_contains "Found archived database container image file .data/db.tar. Expanding..."
  assert_output_contains "Found expanded myorg/myapp image on host."
  assert_output_contains "[ OK ] Finished database data container image download."

  # Clean up
  rm -f .data/db.tar

  popd >/dev/null
}

@test "download-db-container-registry: Use base image when archive not found and base image provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'pulled base image'" 2

  # Mock the login script
  mock_set_side_effect "$(mock_command "./scripts/vortex/login-container-registry.sh")" "echo 'logged in'" 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DB_IMAGE_BASE="myorg/base"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_DB_DIR=".data"

  run scripts/vortex/download-db-container-registry.sh
  assert_success
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "Database container image was not found. Using base image myorg/base."
  assert_output_contains "Downloading myorg/base image from the registry."
  assert_output_contains "[ OK ] Finished database data container image download."

  popd >/dev/null
}

@test "download-db-container-registry: Skip download when image already exists on host" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  # First call to image inspect succeeds (image found on host)
  mock_set_side_effect "${mock_docker}" "echo 'image exists'" 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_DB_DIR=".data"

  run scripts/vortex/download-db-container-registry.sh
  assert_success
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "Found myorg/myapp image on host."
  assert_output_contains "[ OK ] Finished database data container image download."

  popd >/dev/null
}

@test "download-db-container-registry: Use default registry when not specified" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_docker=$(mock_command "docker")
  mock_set_side_effect "${mock_docker}" "exit 1" 1
  mock_set_side_effect "${mock_docker}" "echo 'pulled from docker.io'" 2

  # Mock the login script
  mock_set_side_effect "$(mock_command "./scripts/vortex/login-container-registry.sh")" "echo 'logged in'" 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  # Don't set VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY to test default
  unset VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY VORTEX_CONTAINER_REGISTRY
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"
  export VORTEX_DB_DIR=".data"

  run scripts/vortex/download-db-container-registry.sh
  assert_success
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "Downloading myorg/myapp image from the registry."
  assert_output_contains "[ OK ] Finished database data container image download."

  popd >/dev/null
}


@test "download-db-container-registry: Fail when VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_USER
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"

  run scripts/vortex/download-db-container-registry.sh
  assert_failure
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER."

  popd >/dev/null
}

@test "download-db-container-registry: Fail when VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_IMAGE="myorg/myapp"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS=""
  # Also unset fallback variable
  unset VORTEX_CONTAINER_REGISTRY_PASS

  run scripts/vortex/download-db-container-registry.sh
  assert_failure
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "[FAIL] Missing required value for VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS."

  popd >/dev/null
}

@test "download-db-container-registry: Fail when VORTEX_DB_IMAGE is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DB_IMAGE=""
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_USER="testuser"
  export VORTEX_DOWNLOAD_DB_CONTAINER_REGISTRY_PASS="testpass"

  run scripts/vortex/download-db-container-registry.sh
  assert_failure
  assert_output_contains "[INFO] Started database data container image download."
  assert_output_contains "[FAIL] Destination image name is not specified. Please provide container image name as a first argument to this script in a format <org>/<repository>."

  popd >/dev/null
}