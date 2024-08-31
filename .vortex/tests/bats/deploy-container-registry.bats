#!/usr/bin/env bats
#
# Tests for scripts/vortex/deploy-container-registry.sh script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Missing VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP - deployment should not proceed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP

  run scripts/vortex/deploy-container-registry.sh
  assert_success
  assert_output_contains "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable. Container registry deployment will not continue."

  popd >/dev/null
}

@test "Container registry deployment with valid VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_CONTAINER_REGISTRY="registry.example.com"
  export VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  provision_docker_config_file VORTEX_DEPLOY_CONTAINER_REGISTRY

  export VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="service1=image1,service2=image2,service3=image3"

  declare -a STEPS=(
    "Started container registry deployment."
    "Processing service service1"
    "@docker compose ps -q service1 # service1_service_id"
    "Found \"service1\" service container with id \"service1_service_id\"."
    "Committing container image with name \"registry.example.com/image1:test_latest\"."
    "@docker commit service1_service_id registry.example.com/image1:test_latest # sha256:service1_image_id"
    "Committed container image with id \"service1_image_id\"."
    "Pushing container image to the registry."
    "@docker push registry.example.com/image1:test_latest"
    "Processing service service2"
    "@docker compose ps -q service2 # service2_service_id"
    "Found \"service2\" service container with id \"service2_service_id\"."
    "Committing container image with name \"registry.example.com/image2:test_latest\"."
    "@docker commit service2_service_id registry.example.com/image2:test_latest # sha256:service2_image_id"
    "Committed container image with id \"service2_image_id\"."
    "Pushing container image to the registry."
    "@docker push registry.example.com/image2:test_latest"
    "Processing service service3"
    "@docker compose ps -q service3 # service3_service_id"
    "Found \"service3\" service container with id \"service3_service_id\"."
    "Committing container image with name \"registry.example.com/image3:test_latest\"."
    "@docker commit service3_service_id registry.example.com/image3:test_latest # sha256:service3_image_id"
    "Committed container image with id \"service3_image_id\"."
    "Pushing container image to the registry."
    "@docker push registry.example.com/image3:test_latest"
    "Finished container registry deployment."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/vortex/deploy-container-registry.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Container registry deployment with services not running" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  export VORTEX_DEPLOY_CONTAINER_REGISTRY="registry.example.com"
  provision_docker_config_file VORTEX_DEPLOY_CONTAINER_REGISTRY

  export VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="service1=image1"

  declare -a STEPS=(
    "Started container registry deployment."
    "Processing service service1"
    "@docker compose ps -q service1"
    "Service \"service1\" is not running."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/vortex/deploy-container-registry.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Invalid VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG="test_latest"
  export VORTEX_DEPLOY_CONTAINER_REGISTRY="registry.example.com"

  # No key/value pair
  export VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="service1"

  run ./scripts/vortex/deploy-container-registry.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1\" provided."

  # Using a space delimiter.
  export VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="service1=image1 service2=image2"

  run scripts/vortex/deploy-container-registry.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1=image1 service2=image2\" provided."

  # No comma delimiter
  export VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP="service1=image1=service2=image2"

  run scripts/vortex/deploy-container-registry.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1=image1=service2=image2\" provided."

  popd >/dev/null
}
