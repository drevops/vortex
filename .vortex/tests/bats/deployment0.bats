#!/usr/bin/env bats
#
# Test runner for deployment tests.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.deployment.bash

@test "Deployment; no integration" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artifact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/deployment_remote}

  step "Starting DEPLOYMENT tests."

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
    fixture_prepare_dir "${SRC_DIR}"

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages of the CI process.
    install_and_build_site "${CURRENT_PROJECT_DIR}"

    substep "Copying built codebase into code source directory ${SRC_DIR}."
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    substep "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${SRC_DIR}"

  # Make sure that one of the excluded directories will be ignored in the
  # deployment artifact.
  mkdir -p "${SRC_DIR}"/web/themes/custom/star_wars/node_modules
  touch "${SRC_DIR}"/web/themes/custom/star_wars/node_modules/test.txt

  substep "Preparing remote repo directory ${REMOTE_REPO_DIR}."
  fixture_prepare_dir "${REMOTE_REPO_DIR}"
  git_init 1 "${REMOTE_REPO_DIR}"

  popd >/dev/null

  pushd "${CURRENT_PROJECT_DIR}" >/dev/null

  substep "Running deployment."
  # This deployment uses all 3 types.
  export VORTEX_DEPLOY_TYPES="artifact,webhook,container_registry"

  # Variables for ARTIFACT deployment.
  export VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="${REMOTE_REPO_DIR}"/.git
  export VORTEX_DEPLOY_ARTIFACT_ROOT="${CURRENT_PROJECT_DIR}"
  export VORTEX_DEPLOY_ARTIFACT_SRC="${SRC_DIR}"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL="${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL:-testuser@example.com}"

  # Variables for WEBHOOK deployment.
  export VORTEX_DEPLOY_WEBHOOK_URL=http://example.com
  export VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS=200

  # Variables for container registry deployment.
  # @todo: Not implemented. Add here when implemented.

  # Run deployment.
  run ahoy deploy
  assert_success

  #
  # Artifact deployment assertions.
  #

  assert_output_contains "Started ARTIFACT deployment."

  substep "ARTIFACT: Assert remote deployment files."
  assert_deployment_files_present "${REMOTE_REPO_DIR}"

  # Assert Acquia integration files are absent.
  assert_files_present_no_integration_acquia "${REMOTE_REPO_DIR}"

  assert_output_contains "Finished ARTIFACT deployment."

  #
  # Webhook deployment assertions.
  #
  assert_output_contains "Started WEBHOOK deployment."
  assert_output_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_contains "Finished WEBHOOK deployment."

  #
  # Container registry deployment assertions.
  #
  # By default, container registry deployment will not proceed if
  # service-to-image map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP
  # variable and will exit normally.
  assert_output_contains "Started container registry deployment."
  assert_output_contains "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable."
  assert_output_not_contains "Finished container registry deployment."

  popd >/dev/null
}

@test "Deployment; no integration; flags" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  step "Starting DEPLOYMENT tests."

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
    fixture_prepare_dir "${SRC_DIR}"

    # Do not build - only structure.
    install_and_build_site "${CURRENT_PROJECT_DIR}" 0

    substep "Copying built codebase into code source directory ${SRC_DIR}."
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    substep "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${SRC_DIR}"

  popd >/dev/null

  pushd "${CURRENT_PROJECT_DIR}" >/dev/null

  substep "Running deployment."

  export VORTEX_DEPLOY_TYPES="webhook"

  # Variables for WEBHOOK deployment.
  export VORTEX_DEPLOY_WEBHOOK_URL=http://example.com
  export VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS=200

  #
  # Test assertions.
  #

  substep "Run deployment without skip flag set."

  run ahoy deploy
  assert_success
  assert_output_not_contains "Skipping deployment webhook."
  assert_output_contains "Started WEBHOOK deployment."
  assert_output_contains "Finished WEBHOOK deployment."

  step "Run deployment with skip flag set, but without per-branch or per-pr skip flags."
  export VORTEX_DEPLOY_ALLOW_SKIP=1

  run ahoy deploy
  assert_success
  assert_output_not_contains "Skipping deployment webhook."
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Started WEBHOOK deployment."
  assert_output_contains "Finished WEBHOOK deployment."

  step "Run deployment with skip flag set and with per-branch flag set."
  export VORTEX_DEPLOY_ALLOW_SKIP=1

  export VORTEX_DEPLOY_BRANCH="feature/test"
  export VORTEX_DEPLOY_SKIP_BRANCH_FEATURE_TEST=1

  run ahoy deploy
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found skip variable VORTEX_DEPLOY_SKIP_BRANCH_FEATURE_TEST for branch feature/test."
  assert_output_contains "Skipping deployment webhook."
  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  step "Run deployment with skip flag set and with per-pr flag set."
  export VORTEX_DEPLOY_ALLOW_SKIP=1

  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PR_123=1

  run ahoy deploy
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found skip variable VORTEX_DEPLOY_SKIP_PR_123 for PR 123."
  assert_output_contains "Skipping deployment webhook."
  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  step "Run deployment without skip flag set and with per-pr flag set."
  unset VORTEX_DEPLOY_ALLOW_SKIP

  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PR_123=1

  run ahoy deploy
  assert_success
  assert_output_not_contains "Found flag to skip a deployment."
  assert_output_not_contains "Found skip variable VORTEX_DEPLOY_SKIP_PR_123 for PR 123."
  assert_output_not_contains "Skipping deployment webhook."
  assert_output_contains "Started WEBHOOK deployment."
  assert_output_contains "Finished WEBHOOK deployment."

  popd >/dev/null
}
