#!/usr/bin/env bats
#
# Tests for scripts/drevops/deploy-docker.sh script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Missing DREVOPS_DEPLOY_DOCKER_MAP - deployment should not proceed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  unset DREVOPS_DEPLOY_DOCKER_MAP
  run scripts/drevops/deploy-docker.sh
  assert_success
  assert_output_contains "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable. Docker deployment will not continue."
  popd >/dev/null
}

@test "Docker deployment with valid DREVOPS_DEPLOY_DOCKER_MAP" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DREVOPS_DOCKER_IMAGE_TAG="test_latest"
  export DOCKER_REGISTRY="registry.example.com"
  provision_docker_config_file DOCKER_REGISTRY
  export DREVOPS_DEPLOY_DOCKER_MAP="service1=image1,service2=image2,service3=image3"

  declare -a STEPS=(
    "Started DOCKER deployment."
    "Processing service service1"
    "@docker compose ps -q service1 # service1_service_id"
    "Found \"service1\" service container with id \"service1_service_id\"."
    "Committing Docker image with name \"${DOCKER_REGISTRY}/image1:${DREVOPS_DOCKER_IMAGE_TAG}\"."
    "@docker commit service1_service_id ${DOCKER_REGISTRY}/image1:${DREVOPS_DOCKER_IMAGE_TAG} # sha256:service1_image_id"
    "Committed Docker image with id \"service1_image_id\"."
    "Pushing Docker image to the registry."
    "@docker push ${DOCKER_REGISTRY}/image1:${DREVOPS_DOCKER_IMAGE_TAG}"
    "Processing service service2"
    "@docker compose ps -q service2 # service2_service_id"
    "Found \"service2\" service container with id \"service2_service_id\"."
    "Committing Docker image with name \"${DOCKER_REGISTRY}/image2:${DREVOPS_DOCKER_IMAGE_TAG}\"."
    "@docker commit service2_service_id ${DOCKER_REGISTRY}/image2:${DREVOPS_DOCKER_IMAGE_TAG} # sha256:service2_image_id"
    "Committed Docker image with id \"service2_image_id\"."
    "Pushing Docker image to the registry."
    "@docker push ${DOCKER_REGISTRY}/image2:${DREVOPS_DOCKER_IMAGE_TAG}"
    "Processing service service3"
    "@docker compose ps -q service3 # service3_service_id"
    "Found \"service3\" service container with id \"service3_service_id\"."
    "Committing Docker image with name \"${DOCKER_REGISTRY}/image3:${DREVOPS_DOCKER_IMAGE_TAG}\"."
    "@docker commit service3_service_id ${DOCKER_REGISTRY}/image3:${DREVOPS_DOCKER_IMAGE_TAG} # sha256:service3_image_id"
    "Committed Docker image with id \"service3_image_id\"."
    "Pushing Docker image to the registry."
    "@docker push ${DOCKER_REGISTRY}/image3:${DREVOPS_DOCKER_IMAGE_TAG}"
    "Finished DOCKER deployment."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/drevops/deploy-docker.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  run scripts/drevops/deploy-docker.sh

  popd >/dev/null
}

@test "Docker deployment with services not running" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DREVOPS_DOCKER_IMAGE_TAG="test_latest"
  export DOCKER_REGISTRY="registry.example.com"
  provision_docker_config_file DOCKER_REGISTRY
  export DREVOPS_DEPLOY_DOCKER_MAP="service1=image1"

  declare -a STEPS=(
    "Started DOCKER deployment."
    "Processing service service1"
    "@docker compose ps -q service1"
    "Service \"service1\" is not running."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/drevops/deploy-docker.sh
  assert_failure

  run_steps "assert" "${mocks[@]}"

  run scripts/drevops/deploy-docker.sh

  popd >/dev/null
}

@test "Invalid DREVOPS_DEPLOY_DOCKER_MAP provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DREVOPS_DOCKER_IMAGE_TAG="test_latest"
  export DOCKER_REGISTRY="registry.example.com"

  # No key/value pair
  export DREVOPS_DEPLOY_DOCKER_MAP="service1"
  run ./scripts/drevops/deploy-docker.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1\" provided."

  # using a space delimiter
  export DREVOPS_DEPLOY_DOCKER_MAP="service1=image1 service2=image2"
  run scripts/drevops/deploy-docker.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1=image1 service2=image2\" provided."

  # No comma delimiter
  export DREVOPS_DEPLOY_DOCKER_MAP="service1=image1=service2=image2"
  run scripts/drevops/deploy-docker.sh
  assert_failure
  assert_output_contains "invalid key/value pair \"service1=image1=service2=image2\" provided."

  popd >/dev/null
}
