#!/usr/bin/env bats
#
# Test runner for deployment tests.
#
# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash
load ../_helper.deployment.bash

@test "Deployment; Acquia integration" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artifact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/deployment_remote}

  step "Starting DEPLOYMENT tests."

  export VORTEX_INSTALL_PROMPT_DEPLOY_TYPES="artifact"

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
    fixture_prepare_dir "${SRC_DIR}"

    # Enable Acquia integration for this test to run independent deployment
    # by using install auto-discovery.
    export VORTEX_DB_DOWNLOAD_SOURCE="acquia"

    # Override download from Acquia with a special flag. This still allows to
    # validate that download script expects credentials, but does not actually
    # run the download (it would fail since there is no Acquia environment
    # attached to this test).
    # A demo test database will be used as actual database to provision site
    # during this test.
    echo "VORTEX_DB_DOWNLOAD_PROCEED=0" >>"${CURRENT_PROJECT_DIR}"/.env

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages.
    install_and_assemble_site "${CURRENT_PROJECT_DIR}" 1 "docroot"

    substep "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    substep "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}" "" "" "" "" "docroot"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_integration_acquia "${SRC_DIR}" "sw" 1 "docroot"
  assert_files_present_no_integration_lagoon "${SRC_DIR}" "" "docroot"
  assert_files_present_no_integration_ftp "${SRC_DIR}"
  assert_git_repo "${SRC_DIR}"

  # Make sure that one of the excluded directories will be ignored in the
  # deployment artifact.
  mkdir -p "${SRC_DIR}"/web/themes/custom/star_wars/node_modules
  touch "${SRC_DIR}"/web/themes/custom/star_wars/node_modules/test.txt

  step "Preparing remote repo directory ${REMOTE_REPO_DIR}"
  fixture_prepare_dir "${REMOTE_REPO_DIR}"
  git_init 1 "${REMOTE_REPO_DIR}"

  popd >/dev/null

  pushd "${CURRENT_PROJECT_DIR}" >/dev/null

  step "Running deployment"
  export VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="${REMOTE_REPO_DIR}"/.git
  export VORTEX_DEPLOY_ARTIFACT_ROOT="${CURRENT_PROJECT_DIR}"
  export VORTEX_DEPLOY_ARTIFACT_SRC="${SRC_DIR}"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL="${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL:-testuser@example.com}"

  run ahoy deploy
  assert_success
  assert_output_contains "Started ARTIFACT deployment."

  # Assert that no other deployments ran.
  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started container registry deployment."
  assert_output_not_contains "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable."
  assert_output_not_contains "Finished container registry deployment."

  assert_output_not_contains "Started LAGOON deployment."
  assert_output_not_contains "Finished LAGOON deployment."

  step "Assert remote deployment files"
  assert_deployment_files_present "${REMOTE_REPO_DIR}" "docroot"

  # Assert Acquia integration files are present.
  assert_files_present_integration_acquia "${REMOTE_REPO_DIR}" "sw" 0 "docroot"

  popd >/dev/null
}

@test "Deployment; Lagoon integration" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  step "Starting DEPLOYMENT tests."

  export VORTEX_INSTALL_PROMPT_DEPLOY_TYPES="lagoon"
  export VORTEX_INSTALL_PROMPT_HOSTING_PROVIDER="lagoon"
  export VORTEX_INSTALL_PROMPT_DEPLOY_TYPES="lagoon"
  export VORTEX_INSTALL_PROMPT_DATABASE_DOWNLOAD_SOURCE="lagoon"

  SRC_DIR="${BUILD_DIR}/deployment_src"
  substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
  fixture_prepare_dir "${SRC_DIR}"

  # Do not assemble - only structure.
  install_and_assemble_site "${CURRENT_PROJECT_DIR}" 0 "${answers[@]}"

  substep "Copying built codebase into code source directory ${SRC_DIR}"
  cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_integration_lagoon "${SRC_DIR}"
  assert_files_present_no_integration_acquia "${SRC_DIR}"
  assert_files_present_no_integration_ftp "${SRC_DIR}"
  assert_git_repo "${SRC_DIR}"

  popd >/dev/null

  pushd "${CURRENT_PROJECT_DIR}" >/dev/null

  step "Running deployment"
  # Always force installing of the Lagoon CLI binary in tests rather than using
  # a local version.
  export VORTEX_LAGOONCLI_FORCE_INSTALL=1
  export VORTEX_LAGOONCLI_PATH="${APP_TMP_DIR}"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="testlagoon"
  export LAGOON_PROJECT="testproject"
  export VORTEX_DEPLOY_BRANCH="testbranch"

  mock_lagoon=$(mock_command "lagoon")

  run ahoy deploy
  assert_success

  assert_output_contains "Started Lagoon deployment."
  assert_output_contains "Installing Lagoon CLI."
  assert_output_contains "Configuring Lagoon instance."
  assert_output_contains "Finished Lagoon deployment."

  # Assert lagoon binary exists and was called.
  assert_file_exists "${VORTEX_LAGOONCLI_PATH}/lagoon"

  assert_equal 3 "$(mock_get_call_num "${mock_lagoon}")"
  # Configure.
  assert_contains "config add --force --lagoon testlagoon --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222" "$(mock_get_call_args "${mock_lagoon}" 1)"
  # Get a list of environments.
  assert_contains "--lagoon testlagoon --project testproject list environments --output-json --pretty" "$(mock_get_call_args "${mock_lagoon}" 2)"
  # Trigger a deployment.
  assert_contains "--lagoon testlagoon --project testproject deploy branch --branch testbranch" "$(mock_get_call_args "${mock_lagoon}" 3)"

  # Assert that no other deployments ran.
  assert_output_not_contains "Started ARTIFACT deployment."
  assert_output_not_contains "Finished ARTIFACT deployment."

  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started container registry deployment."
  assert_output_not_contains "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable."
  assert_output_not_contains "Finished container registry deployment."

  popd >/dev/null
}

@test "Deployment; Lagoon integration; PROVISION_TYPE_PROFILE; redeploy" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  step "Starting DEPLOYMENT tests."

  SRC_DIR="${BUILD_DIR}/deployment_src"
  substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
  fixture_prepare_dir "${SRC_DIR}"

  export VORTEX_INSTALL_PROMPT_DEPLOY_TYPES="lagoon"
  export VORTEX_INSTALL_PROMPT_HOSTING_PROVIDER="lagoon"
  export VORTEX_INSTALL_PROMPT_DEPLOY_TYPES="lagoon"
  export VORTEX_INSTALL_PROMPT_DATABASE_DOWNLOAD_SOURCE="lagoon"
  export VORTEX_INSTALL_PROMPT_PROVISION_TYPE="profile"

  # Do not build - only structure.
  install_and_assemble_site "${CURRENT_PROJECT_DIR}" 0

  substep "Copying built codebase into code source directory ${SRC_DIR}"
  cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_integration_lagoon "${SRC_DIR}"
  assert_files_present_no_integration_acquia "${SRC_DIR}"
  assert_files_present_no_integration_ftp "${SRC_DIR}"
  assert_git_repo "${SRC_DIR}"

  popd >/dev/null

  pushd "${CURRENT_PROJECT_DIR}" >/dev/null

  step "Running deployment"
  # Always force installing of the Lagoon CLI binary in tests rather than using
  # a local version.
  export VORTEX_LAGOONCLI_FORCE_INSTALL=1
  export VORTEX_LAGOONCLI_PATH="${APP_TMP_DIR}"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="testlagoon"
  export LAGOON_PROJECT="testproject"
  export VORTEX_DEPLOY_BRANCH="testbranch"

  mock_lagoon=$(mock_command "lagoon")
  # Configuring.
  mock_set_output "${mock_lagoon}" "success" 1
  # Existing environment.
  mock_set_output "${mock_lagoon}" '{"data": [{"deploytype": "branch", "environment": "development", "id": "364889", "name": "testbranch", "openshiftprojectname": "testproject-testbranch", "route": "https://nginx-php.develop.civic.au2.amazee.io"}]}' 2
  # Set variables.
  mock_set_output "${mock_lagoon}" "success" 3
  # Set variables.
  mock_set_output "${mock_lagoon}" "success" 4
  # Redeploying env.
  mock_set_output "${mock_lagoon}" "success" 5
  # Remove variables.
  mock_set_output "${mock_lagoon}" "success" 6

  # Deployment action to override db.
  export VORTEX_DEPLOY_ACTION="deploy_override_db"

  run ahoy deploy
  assert_success

  assert_output_contains "Started Lagoon deployment."
  assert_output_contains 'Found already deployed environment for branch "testbranch".'
  assert_output_contains "Adding a DB import override flag for the current deployment."
  assert_output_contains "Redeploying environment: project testproject, branch: testbranch."
  assert_output_contains "Waiting for deployment to be queued."
  assert_output_contains "Removing a DB import override flag for the current deployment."
  assert_output_contains "Finished Lagoon deployment."

  # Assert lagoon binary exists and was called.
  assert_file_exists "${VORTEX_LAGOONCLI_PATH}/lagoon"

  # Deployment script calls API several times.
  assert_equal 6 "$(mock_get_call_num "${mock_lagoon}")"

  # Configure.
  assert_contains "config add --force --lagoon testlagoon --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222" "$(mock_get_call_args "${mock_lagoon}" 1)"
  # Get a list of environments.
  assert_contains "--lagoon testlagoon --project testproject list environments --output-json --pretty" "$(mock_get_call_args "${mock_lagoon}" 2)"
  # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
  assert_contains "--lagoon testlagoon --project testproject update variable --environment testbranch --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global" "$(mock_get_call_args "${mock_lagoon}" 3)"
  # Override DB during re-deployment.
  assert_contains "--lagoon testlagoon --project testproject update variable --environment testbranch --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global" "$(mock_get_call_args "${mock_lagoon}" 4)"
  # Redeploy.
  assert_contains "--lagoon testlagoon --project testproject deploy latest --environment testbranch" "$(mock_get_call_args "${mock_lagoon}" 5)"
  # Remove a DB import override flag for the current deployment.
  assert_contains "--lagoon testlagoon --project testproject update variable --environment testbranch --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global" "$(mock_get_call_args "${mock_lagoon}" 6)"

  # Assert that no other deployments ran.
  assert_output_not_contains "Started ARTIFACT deployment."
  assert_output_not_contains "Finished ARTIFACT deployment."

  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started container registry deployment."
  assert_output_not_contains "Services map is not specified in VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP variable."
  assert_output_not_contains "Finished container registry deployment."

  popd >/dev/null
}
