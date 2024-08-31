#!/usr/bin/env bats
#
# Test for CircleCI lifecycle.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Missing or Invalid VORTEX_DEPLOY_TYPES" {
  substep "Swap to ${LOCAL_REPO_DIR}"
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES=""
  run ahoy deploy
  assert_failure

  assert_output_contains "Missing required value for VORTEX_DEPLOY_TYPES. Must be a combination of comma-separated values (to support multiple deployments): code, container_registry, webhook, lagoon."

  popd >/dev/null
}

@test "Check setting and default values for required variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Note the following variables have default values
  # so the check for empty string is redundant:
  #  - VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME
  #  - VORTEX_DEPLOY_ARTIFACT_ROOT
  #  - VORTEX_DEPLOY_ARTIFACT_LOG

  unset VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE
  unset VORTEX_DEPLOY_ARTIFACT_DST_BRANCH
  unset VORTEX_DEPLOY_ARTIFACT_SRC
  unset VORTEX_DEPLOY_ARTIFACT_ROOT
  unset VORTEX_DEPLOY_ARTIFACT_LOG

  run scripts/vortex/deploy-artifact.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE."

  export VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="git@github.com:yourorg/your-repo-destination.git"

  run scripts/vortex/deploy-artifact.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_ARTIFACT_SRC."

  export VORTEX_DEPLOY_ARTIFACT_SRC="dist"

  run scripts/vortex/deploy-artifact.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL."

  popd >/dev/null
}

@test "Artifact deployment, global git username and email configured, default SSH Key" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  setup_robo_fixture
  provision_default_ssh_key
  export VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="git@github.com:yourorg/your-repo-destination.git"
  export VORTEX_DEPLOY_ARTIFACT_DST_BRANCH="main"
  export VORTEX_DEPLOY_ARTIFACT_SRC="dist"
  export VORTEX_DEPLOY_ARTIFACT_ROOT="."
  export VORTEX_DEPLOY_ARTIFACT_LOG="deploy-report.txt"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME="test_user"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL="test_user@example.com"
  local file=${HOME}/.ssh/id_rsa

  mock_realpath=$(mock_command "realpath")

  declare -a STEPS=(
    "- Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE."
    "- Missing required value for VORTEX_DEPLOY_ARTIFACT_SRC."
    "- Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL."
    "@git config --global user.name #"
    "Configuring global git user name."
    "@git config --global user.name ${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME} # 0 #"
    "@git config --global user.email #"
    "Configuring global git user email."
    "@git config --global user.email ${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL} # 0 #"
    "Using default SSH file ${file}."
    "Using SSH key file ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent has ${file} key loaded."
    "Installing artifact builder."
    "@composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:^0.7"
    "Running artifact builder."
    "Finished ARTIFACT deployment."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-artifact.sh
  assert_success

  run_steps "assert" "${mocks[@]}"
  assert_equal "2" "$(mock_get_call_num "${mock_realpath}" 1)"

  popd >/dev/null
}
