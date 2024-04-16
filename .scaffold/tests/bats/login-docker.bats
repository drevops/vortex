#!/usr/bin/env bats
#
# Test for login-docker script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Docker configuration present" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DOCKER_REGISTRY="https://www.example.com"
  provision_docker_config_file $DOCKER_REGISTRY
  export HOME=${BUILD_DIR}
  run scripts/drevops/login-docker.sh
  assert_success
  assert_output_contains Already logged in to registry \"https://www.example.com\"
  popd >/dev/null
}

@test "DOCKER_REGISTRY, DOCKER_USER and DOCKER_PASS must be set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export HOME=${BUILD_DIR}
  unset DOCKER_USER
  unset DOCKER_PASS
  run scripts/drevops/login-docker.sh
  assert_success
  assert_output_not_contains "Missing required value for DOCKER_REGISTRY."
  assert_output_contains "Skipping login into Docker registry as either DOCKER_USER or DOCKER_PASS was not provided."
  export DOCKER_USER="test_user"
  run scripts/drevops/login-docker.sh
  assert_success
  assert_output_contains "Skipping login into Docker registry as either DOCKER_USER or DOCKER_PASS was not provided."
  mock_docker=$(mock_command "docker")
  mock_set_output "${mock_docker}" "Login Succeeded" 1
  export DOCKER_PASS="test_pass"
  export DOCKER_REGISTRY="https://www.example.com"
  run scripts/drevops/login-docker.sh
  assert_success
  assert_equal "1" "$(mock_get_call_num "${mock_docker}" 1)"
  assert_output_contains "Logging in to registry \"https://www.example.com\"."
  popd >/dev/null
}
