#!/usr/bin/env bats
#
# Tests for .vortex/tooling/src/push-container-registry script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155,SC2034

load ../_helper.bash
setup_robo_fixture() {
  export HOME="${BUILD_DIR}"
  fixture_prepare_dir "${HOME}/.composer/vendor/bin"
  touch "${HOME}/.composer/vendor/bin/robo"
  chmod +x "${HOME}/.composer/vendor/bin/robo"

  # Also create a mock for git-artifact
  touch "${HOME}/.composer/vendor/bin/git-artifact"
  chmod +x "${HOME}/.composer/vendor/bin/git-artifact"
}

@test "Missing VORTEX_PUSH_CONTAINER_REGISTRY_MAP - push should not proceed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  unset VORTEX_PUSH_CONTAINER_REGISTRY_MAP

  run .vortex/tooling/src/push-container-registry
  assert_success
  assert_output_contains "Services map is not specified in VORTEX_PUSH_CONTAINER_REGISTRY_MAP variable. Container registry push will not continue."

  popd >/dev/null
}

@test "Empty VORTEX_PUSH_CONTAINER_REGISTRY_MAP skips before requiring credentials" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DOCKER_CONFIG=/dev/null

  # No map and no credentials provided.
  unset VORTEX_PUSH_CONTAINER_REGISTRY_MAP
  unset VORTEX_CONTAINER_REGISTRY_USER
  unset VORTEX_CONTAINER_REGISTRY_PASS
  unset VORTEX_PUSH_CONTAINER_REGISTRY_USER
  unset VORTEX_PUSH_CONTAINER_REGISTRY_PASS

  run .vortex/tooling/src/push-container-registry
  assert_success
  assert_output_contains "Services map is not specified in VORTEX_PUSH_CONTAINER_REGISTRY_MAP variable. Container registry push will not continue."
  assert_output_not_contains "Missing required value"

  popd >/dev/null
}

@test "Container registry push with valid VORTEX_PUSH_CONTAINER_REGISTRY_MAP" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  export VORTEX_PUSH_CONTAINER_REGISTRY="registry.example.com"
  fixture_docker_config_file "${VORTEX_PUSH_CONTAINER_REGISTRY}"
  export VORTEX_PUSH_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"

  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=image1,service2=image2,service3=image3"

  declare -a STEPS=(
    "Started container registry push."
    "@docker login --username test_user --password-stdin registry.example.com"
    "Processing service service1"
    "@docker compose ps -q service1 # service1_service_id"
    'Found "service1" service container with id "service1_service_id".'
    'Committing container image with name "registry.example.com/image1:test_latest".'
    "@docker commit service1_service_id registry.example.com/image1:test_latest # sha256:service1_image_id"
    'Committed container image with id "service1_image_id".'
    "Pushing container image to the registry."
    "@docker push registry.example.com/image1:test_latest"
    "Processing service service2"
    "@docker compose ps -q service2 # service2_service_id"
    'Found "service2" service container with id "service2_service_id".'
    'Committing container image with name "registry.example.com/image2:test_latest".'
    "@docker commit service2_service_id registry.example.com/image2:test_latest # sha256:service2_image_id"
    'Committed container image with id "service2_image_id".'
    "Pushing container image to the registry."
    "@docker push registry.example.com/image2:test_latest"
    "Processing service service3"
    "@docker compose ps -q service3 # service3_service_id"
    'Found "service3" service container with id "service3_service_id".'
    'Committing container image with name "registry.example.com/image3:test_latest".'
    "@docker commit service3_service_id registry.example.com/image3:test_latest # sha256:service3_image_id"
    'Committed container image with id "service3_image_id".'
    "Pushing container image to the registry."
    "@docker push registry.example.com/image3:test_latest"
    "Finished container registry push."
  )

  mocks="$(run_steps "setup")"

  run ./.vortex/tooling/src/push-container-registry
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Container registry push with services not running" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  export VORTEX_PUSH_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  export VORTEX_PUSH_CONTAINER_REGISTRY="registry.example.com"
  fixture_docker_config_file "${VORTEX_PUSH_CONTAINER_REGISTRY}"

  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=image1"

  declare -a STEPS=(
    "Started container registry push."
    "@docker login --username test_user --password-stdin registry.example.com"
    "Processing service service1"
    "@docker compose ps -q service1"
    'Service "service1" is not running.'
  )

  mocks="$(run_steps "setup")"

  run ./.vortex/tooling/src/push-container-registry
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Invalid VORTEX_PUSH_CONTAINER_REGISTRY_MAP provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Override any existing values in the current environment.
  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  export VORTEX_PUSH_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  export VORTEX_PUSH_CONTAINER_REGISTRY="registry.example.com"

  # No key/value pair
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1"

  run ./.vortex/tooling/src/push-container-registry
  assert_failure
  assert_output_contains 'Invalid key/value pair "service1" provided.'

  # Using a space delimiter.
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=image1 service2=image2"

  run .vortex/tooling/src/push-container-registry
  assert_failure
  assert_output_contains 'Invalid key/value pair "service1=image1 service2=image2" provided.'

  # No comma delimiter
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=image1=service2=image2"

  run .vortex/tooling/src/push-container-registry
  assert_failure
  assert_output_contains 'Invalid key/value pair "service1=image1=service2=image2" provided.'

  # Empty image.
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1="

  run .vortex/tooling/src/push-container-registry
  assert_failure
  assert_output_contains 'Invalid key/value pair "service1=" provided.'

  # Empty service.
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="=image1"

  run .vortex/tooling/src/push-container-registry
  assert_failure
  assert_output_contains 'Invalid key/value pair "=image1" provided.'

  popd >/dev/null
}

@test "Container registry push tags only on the final reference segment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="test_pass"
  export DOCKER_CONFIG=/dev/null

  export VORTEX_PUSH_CONTAINER_REGISTRY="registry.example.com"
  fixture_docker_config_file "${VORTEX_PUSH_CONTAINER_REGISTRY}"
  export VORTEX_PUSH_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"

  # service1 already carries a tag and must be left as-is; service2 carries a
  # registry host:port that must not be mistaken for a tag.
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=org/image1:custom,service2=host.io:5000/app"

  declare -a STEPS=(
    "Started container registry push."
    "@docker login --username test_user --password-stdin registry.example.com"
    "Processing service service1"
    "@docker compose ps -q service1 # service1_service_id"
    'Committing container image with name "registry.example.com/org/image1:custom".'
    "@docker commit service1_service_id registry.example.com/org/image1:custom # sha256:service1_image_id"
    "@docker push registry.example.com/org/image1:custom"
    "Processing service service2"
    "@docker compose ps -q service2 # service2_service_id"
    'Committing container image with name "registry.example.com/host.io:5000/app:test_latest".'
    "@docker commit service2_service_id registry.example.com/host.io:5000/app:test_latest # sha256:service2_image_id"
    "@docker push registry.example.com/host.io:5000/app:test_latest"
    "Finished container registry push."
  )

  mocks="$(run_steps "setup")"

  run ./.vortex/tooling/src/push-container-registry
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Container registry push does not expose the password under VORTEX_DEBUG" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_CONTAINER_REGISTRY_USER="test_user"
  export VORTEX_CONTAINER_REGISTRY_PASS="supersecretpass"
  export DOCKER_CONFIG=/dev/null

  export VORTEX_DEBUG=1
  export VORTEX_PUSH_CONTAINER_REGISTRY="registry.example.com"
  fixture_docker_config_file "${VORTEX_PUSH_CONTAINER_REGISTRY}"
  export VORTEX_PUSH_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  export VORTEX_PUSH_CONTAINER_REGISTRY_MAP="service1=image1"

  declare -a STEPS=(
    "@docker login --username test_user --password-stdin registry.example.com"
    "@docker compose ps -q service1 # service1_service_id"
    "@docker commit service1_service_id registry.example.com/image1:test_latest # sha256:service1_image_id"
    "@docker push registry.example.com/image1:test_latest"
  )

  mocks="$(run_steps "setup")"

  run ./.vortex/tooling/src/push-container-registry
  assert_success
  assert_output_not_contains "supersecretpass"

  popd >/dev/null
}
