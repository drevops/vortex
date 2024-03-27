#!/usr/bin/env bats
#
# Test runner for deployment tests.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.deployment.bash

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

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
    fixture_prepare_dir "${SRC_DIR}"

    # Enable Acquia integration for this test to run independent deployment
    # by using install auto-discovery.
    export DREVOPS_DB_DOWNLOAD_SOURCE="acquia"

    # Override download from Acquia with a special flag. This still allows to
    # validate that download script expects credentials, but does not actually
    # run the download (it would fail since there is no Acquia environment
    # attached to this test).
    # A demo test database will be used as actual database to provision site
    # during this test.
    echo "DREVOPS_DB_DOWNLOAD_PROCEED=0" >>"${CURRENT_PROJECT_DIR}"/.env

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages.
    install_and_build_site "${CURRENT_PROJECT_DIR}"

    substep "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    substep "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_integration_acquia "${SRC_DIR}" "sw" 1
  assert_files_present_no_integration_lagoon "${SRC_DIR}"
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
  export DREVOPS_DEPLOY_ARTIFACT_GIT_REMOTE="${REMOTE_REPO_DIR}"/.git
  export DREVOPS_DEPLOY_ARTIFACT_ROOT="${CURRENT_PROJECT_DIR}"
  export DREVOPS_DEPLOY_ARTIFACT_SRC="${SRC_DIR}"
  export DREVOPS_DEPLOY_ARTIFACT_GIT_USER_EMAIL="${DREVOPS_DEPLOY_ARTIFACT_GIT_USER_EMAIL:-testuser@example.com}"
  export DREVOPS_DEPLOY_TYPES="artifact"

  run ahoy deploy
  assert_success
  assert_output_contains "Started ARTIFACT deployment."

  # Assert that no other deployments ran.
  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started DOCKER deployment."
  assert_output_not_contains "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable."
  assert_output_not_contains "Finished DOCKER deployment."

  assert_output_not_contains "Started LAGOON deployment."
  assert_output_not_contains "Finished LAGOON deployment."

  step "Assert remote deployment files"
  assert_deployment_files_present "${REMOTE_REPO_DIR}"

  # Assert Acquia integration files are present.
  assert_files_present_integration_acquia "${REMOTE_REPO_DIR}" "sw" 0

  popd >/dev/null
}

@test "Deployment; Lagoon integration" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  step "Starting DEPLOYMENT tests."

  SRC_DIR="${BUILD_DIR}/deployment_src"
  substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
  fixture_prepare_dir "${SRC_DIR}"

  # Provision the codebase with Lagoon deployment type and Lagoon integration.
  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # database_download_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "lagoon"    # deploy_type
    "n"         # preserve_ftp
    "n"         # preserve_acquia
    "y"         # preserve_lagoon
    "n"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_drevops_info
  )

  # Do not build - only structure.
  install_and_build_site "${CURRENT_PROJECT_DIR}" 0 "${answers[@]}"

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
  export DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL=1
  export DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH="${APP_TMP_DIR}"
  export DREVOPS_DEPLOY_LAGOON_INSTANCE="testlagoon"
  export LAGOON_PROJECT="testproject"
  export DREVOPS_DEPLOY_BRANCH="testbranch"

  mock_lagoon=$(mock_command "lagoon")

  run ahoy deploy
  assert_success

  assert_output_contains "Started Lagoon deployment."
  assert_output_contains "Finished Lagoon deployment."

  # Assert lagoon binary exists and was called.
  assert_file_exists "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"

  assert_equal 3 "$(mock_get_call_num "${mock_lagoon}")"
  # Configure.
  assert_contains "config add --force -l testlagoon -g https://api.lagoon.amazeeio.cloud/graphql -H ssh.lagoon.amazeeio.cloud -P 32222" "$(mock_get_call_args "${mock_lagoon}" 1)"
  # Get a list of environments.
  assert_contains "-l testlagoon -p testproject list environments --output-json --pretty" "$(mock_get_call_args "${mock_lagoon}" 2)"
  # Trigger a deployment.
  assert_contains "-l testlagoon -p testproject deploy branch -b testbranch" "$(mock_get_call_args "${mock_lagoon}" 3)"

  # Assert that no other deployments ran.
  assert_output_not_contains "Started ARTIFACT deployment."
  assert_output_not_contains "Finished ARTIFACT deployment."

  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started DOCKER deployment."
  assert_output_not_contains "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable."
  assert_output_not_contains "Finished DOCKER deployment."

  popd >/dev/null
}

@test "Deployment; Lagoon integration; provision_use_profile; redeploy" {
  pushd "${BUILD_DIR}" >/dev/null || exit 1

  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  step "Starting DEPLOYMENT tests."

  SRC_DIR="${BUILD_DIR}/deployment_src"
  substep "Deployment source directory is not provided - using directory ${SRC_DIR}"
  fixture_prepare_dir "${SRC_DIR}"

  # Provision the codebase with Lagoon deployment type and Lagoon integration.
  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "y"         # provision_use_profile
    "n"         # override_existing_db
    "lagoon"    # deploy_type
    "n"         # preserve_ftp
    "n"         # preserve_acquia
    "y"         # preserve_lagoon
    "n"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_drevops_info
  )

  # Do not build - only structure.
  install_and_build_site "${CURRENT_PROJECT_DIR}" 0 "${answers[@]}"

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
  export DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL=1
  export DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH="${APP_TMP_DIR}"
  export DREVOPS_DEPLOY_LAGOON_INSTANCE="testlagoon"
  export LAGOON_PROJECT="testproject"
  export DREVOPS_DEPLOY_BRANCH="testbranch"

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
  export DREVOPS_DEPLOY_ACTION="deploy_override_db"

  run ahoy deploy
  assert_success

  assert_output_contains "Started Lagoon deployment."
  assert_output_contains 'Found already deployed environment for branch "testbranch".'
  assert_output_contains "Add a DB import override flag for the current deployment."
  assert_output_contains "Redeploying environment: project testproject, branch: testbranch."
  assert_output_contains "Waiting for deployment to be queued."
  assert_output_contains "Remove a DB import override flag for the current deployment."
  assert_output_contains "Finished Lagoon deployment."

  # Assert lagoon binary exists and was called.
  assert_file_exists "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"

  # Deployment script calls API several times.
  assert_equal 6 "$(mock_get_call_num "${mock_lagoon}")"

  # Configure.
  assert_contains "config add --force -l testlagoon -g https://api.lagoon.amazeeio.cloud/graphql -H ssh.lagoon.amazeeio.cloud -P 32222" "$(mock_get_call_args "${mock_lagoon}" 1)"
  # Get a list of environments.
  assert_contains "-l testlagoon -p testproject list environments --output-json --pretty" "$(mock_get_call_args "${mock_lagoon}" 2)"
  # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
  assert_contains "-l testlagoon -p testproject update variable -e testbranch -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global" "$(mock_get_call_args "${mock_lagoon}" 3)"
  # Override DB during re-deployment.
  assert_contains "-l testlagoon -p testproject update variable -e testbranch -N DREVOPS_PROVISION_OVERRIDE_DB -V 1 -S global" "$(mock_get_call_args "${mock_lagoon}" 4)"
  # Redeploy.
  assert_contains "-l testlagoon -p testproject deploy latest -e testbranch" "$(mock_get_call_args "${mock_lagoon}" 5)"
  # Remove a DB import override flag for the current deployment.
  assert_contains "-l testlagoon -p testproject update variable -e testbranch -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global" "$(mock_get_call_args "${mock_lagoon}" 6)"

  # Assert that no other deployments ran.
  assert_output_not_contains "Started ARTIFACT deployment."
  assert_output_not_contains "Finished ARTIFACT deployment."

  assert_output_not_contains "Started WEBHOOK deployment."
  assert_output_not_contains "Webhook call completed."
  assert_output_not_contains "[FAIL] Unable to complete webhook deployment."
  assert_output_not_contains "Finished WEBHOOK deployment."

  assert_output_not_contains "Started DOCKER deployment."
  assert_output_not_contains "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable."
  assert_output_not_contains "Finished DOCKER deployment."

  popd >/dev/null
}
