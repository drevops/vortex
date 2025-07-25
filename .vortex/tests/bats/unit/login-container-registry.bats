#!/usr/bin/env bats
#
# Test for login-container-registry.sh.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load ../_helper.bash
load ../_helper.deployment.bash

@test "VORTEX_CONTAINER_REGISTRY value is not valid" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  # Set to an invalid value to test the error handling.
  export VORTEX_CONTAINER_REGISTRY=" "

  run scripts/vortex/login-container-registry.sh
  assert_failure
  assert_output_contains "VORTEX_CONTAINER_REGISTRY should not be empty."

  popd >/dev/null
}

@test "User is already logged in to the container registry" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export VORTEX_CONTAINER_REGISTRY="https://www.example.com"

  # Mock the Docker configuration file.
  create_docker_config_file "${VORTEX_CONTAINER_REGISTRY}"
  export DOCKER_CONFIG="${BUILD_DIR}/.docker"

  run scripts/vortex/login-container-registry.sh
  assert_success

  assert_output_contains "Already logged in to the registry \"https://www.example.com\"."

  popd >/dev/null
}

@test "VORTEX_CONTAINER_REGISTRY_USER and VORTEX_CONTAINER_REGISTRY_PASS must be set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DOCKER_CONFIG=/dev/null

  # Unset the variables to test the error handling.
  unset VORTEX_CONTAINER_REGISTRY_USER
  unset VORTEX_CONTAINER_REGISTRY_PASS

  run scripts/vortex/login-container-registry.sh
  assert_success
  assert_output_contains "Skipping login to the container registry as either VORTEX_CONTAINER_REGISTRY_USER or VORTEX_CONTAINER_REGISTRY_PASS was not provided."

  popd >/dev/null
}

@test "Login to container registry with valid credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null
  export VORTEX_CONTAINER_REGISTRY="https://www.example.com"

  declare -a STEPS=(
    "@docker login --username test_user --password-stdin https://www.example.com # 0 # Login Succeeded"
    "Logging in to registry \"https://www.example.com\"."
  )

  mocks="$(run_steps "setup")"
  run scripts/vortex/login-container-registry.sh
  assert_success
  run_steps "assert" "${mocks}"

  popd >/dev/null
}
