#!/usr/bin/env bats
#
# Test runner for deployment tests.
#

load _helper
load _helper_drevops
load _helper_drevops_deployment

@test "Deployment; no integration" {
  pushd "${BUILD_DIR}" > /dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artifact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/deployment_remote}

  step "Starting DEPLOYMENT tests"

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
    prepare_fixture_dir "${SRC_DIR}"

    # Disable Acquia integration for this test to run independent deployment.
    export DREVOPS_OPT_PRESERVE_ACQUIA=0

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages of the CI process.
    provision_site "${CURRENT_PROJECT_DIR}"

    assert_files_present_common "star_wars" "StarWars" "${CURRENT_PROJECT_DIR}"
    assert_files_present_deployment "star_wars" "${CURRENT_PROJECT_DIR}"
    assert_files_present_no_integration_acquia "star_wars" "${CURRENT_PROJECT_DIR}"
    assert_files_present_integration_lagoon "star_wars" "${CURRENT_PROJECT_DIR}"
    assert_files_present_no_integration_ftp "star_wars" "${CURRENT_PROJECT_DIR}"

    substep "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    substep "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.

  assert_files_present_common "star_wars" "StarWars" "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "star_wars" "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "star_wars" "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "star_wars" "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "star_wars" "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${SRC_DIR}"

  # Make sure that one of the excluded directories will be ignored in the
  # deployment artifact.
  mkdir -p "${SRC_DIR}"/docroot/themes/custom/star_wars/node_modules
  touch "${SRC_DIR}"/docroot/themes/custom/star_wars/node_modules/test.txt

  substep "Preparing remote repo directory ${REMOTE_REPO_DIR}"
  prepare_fixture_dir "${REMOTE_REPO_DIR}"
  git_init 1 "${REMOTE_REPO_DIR}"

  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  substep "Running deployment"
  # This deployment uses all 3 types.
  export DEPLOY_TYPE="code,webhook,docker"

  # Variables for CODE deployment.
  export DEPLOY_GIT_REMOTE="${REMOTE_REPO_DIR}"/.git
  export DEPLOY_CODE_ROOT="${CURRENT_PROJECT_DIR}"
  export DEPLOY_CODE_SRC="${SRC_DIR}"

  # Variables for WEBHOOK deployment.
  export DEPLOY_WEBHOOK_URL=http://example.com
  export DEPLOY_WEBHOOK_RESPONSE_STATUS=200

  # Variables for DOCKER deployment.
  # @todo: Not implemented. Add here when implemented.

  # Run deployment.
  run source scripts/drevops/deploy.sh
  assert_success

  #
  # Code deployment assertions.
  #
  assert_output_contains "==> Started CODE deployment"

  substep "CODE: Checkout currently pushed branch on remote"
  git --git-dir="${DEPLOY_GIT_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch | sed 's/\*\s//g' | xargs git --git-dir="${DEPLOY_GIT_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" checkout
  git --git-dir="${DEPLOY_GIT_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch >&3

  substep "CODE: Assert remote deployment files"
  assert_deployment_files_present "${REMOTE_REPO_DIR}"

  # Assert Acquia hooks are absent.
  assert_files_present_no_integration_acquia "${REMOTE_REPO_DIR}"

  assert_output_contains "==> Finished CODE deployment"

  #
  # Webhook deployment assertions.
  #
  assert_output_contains "==> Started WEBHOOK deployment"
  assert_output_contains "==> Successfully called webhook"
  assert_output_not_contains "ERROR: Webhook deployment failed"
  assert_output_contains "==> Finished WEBHOOK deployment"

  #
  # Docker deployment assertions.
  #
  assert_output_contains "==> Started DOCKER deployment"
  # @todo: Update once Docker deployment is implemented.
  assert_output_contains "Docker deployment is not yet implemented"
  assert_output_contains "==> Finished DOCKER deployment"

  popd > /dev/null

  popd > /dev/null
}
