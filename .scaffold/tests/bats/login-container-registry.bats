#!/usr/bin/env bats
#
# Test for login-container-registry.sh.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Docker configuration present" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_CONTAINER_REGISTRY="https://www.example.com"
  provision_docker_config_file $CONTAINER_REGISTRY
  export HOME=${BUILD_DIR}

  run scripts/drevops/login-container-registry.sh
  assert_success
  assert_output_contains Already logged in to the registry \"https://www.example.com\"

  popd >/dev/null
}

@test "CONTAINER_REGISTRY, DREVOPS_CONTAINER_REGISTRY_USER and DREVOPS_CONTAINER_REGISTRY_PASS must be set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export HOME=${BUILD_DIR}
  unset DREVOPS_CONTAINER_REGISTRY_USER
  unset DREVOPS_CONTAINER_REGISTRY_PASS

  run scripts/drevops/login-container-registry.sh
  assert_success
  assert_output_not_contains "Missing required value for DREVOPS_CONTAINER_REGISTRY."
  assert_output_contains "Skipping login into the container registry as eithe DREVOPS_CONTAINER_REGISTRY_USER or DREVOPS_CONTAINER_REGISTRY_PASS was not provided."

  export DREVOPS_CONTAINER_REGISTRY_USER="test_user"

  run scripts/drevops/login-container-registry.sh
  assert_success
  assert_output_contains "Skipping login into the container registry as eithe DREVOPS_CONTAINER_REGISTRY_USER or DREVOPS_CONTAINER_REGISTRY_PASS was not provided."

  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Login Succeeded" 1
  export DREVOPS_CONTAINER_REGISTRY_PASS="test_pass"
  export DREVOPS_CONTAINER_REGISTRY="https://www.example.com"

  run scripts/drevops/login-container-registry.sh
  assert_success
  assert_equal "1" "$(mock_get_call_num "${mock_docker}" 1)"
  assert_output_contains "Logging in to registry \"https://www.example.com\"."

  popd >/dev/null
}
