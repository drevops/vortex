#!/usr/bin/env bats
##
# Unit tests for the 'vortex-task-custom-lagoon' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "task-custom-lagoon: runs the task against the configured instance" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_TASK_CUSTOM_LAGOON_NAME="Test task"
  export VORTEX_TASK_CUSTOM_LAGOON_BRANCH="test-branch"
  export VORTEX_TASK_CUSTOM_LAGOON_COMMAND="drush status"

  declare -a STEPS=(
    "[INFO] Started Lagoon task Test task."
    "@ssh-add -l # ${HOME}/.ssh/id_rsa"

    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "[ OK ] Configured Lagoon instance."

    "Creating Test task task: project: test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project run custom --environment test-branch --name Test task --command drush status"
    "[ OK ] Created Test task task: project: test_project, branch: test-branch."

    "[ OK ] Finished Lagoon task Test task."

    "- Installing Lagoon CLI."
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-custom-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-custom-lagoon: installs the CLI when the installation is forced" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_TASK_CUSTOM_LAGOON_NAME="Test task"
  export VORTEX_TASK_CUSTOM_LAGOON_BRANCH="test-branch"
  export VORTEX_TASK_CUSTOM_LAGOON_COMMAND="drush status"
  export VORTEX_TASK_CUSTOM_LAGOON_CLI_FORCE_INSTALL="1"
  export VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH="${BUILD_DIR}"
  export VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION="v0.32.0"

  # The release asset name carries the running platform and architecture, so
  # the expected URL is resolved the same way the script resolves it.
  platform=$(uname -s | tr '[:upper:]' '[:lower:]')
  arch_suffix=$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/')
  download_url="https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-${platform}-${arch_suffix}"

  declare -a STEPS=(
    "Installing Lagoon CLI."
    "@ssh-add -l # ${HOME}/.ssh/id_rsa"

    "Downloading Lagoon CLI from ${download_url}."
    "@curl -fSLs -o ${BUILD_DIR}/lagoon ${download_url} # 0 # # touch ${BUILD_DIR}/lagoon"
    "Installing Lagoon CLI to ${BUILD_DIR}/lagoon."
    "[ OK ] Installed Lagoon CLI."

    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project run custom --environment test-branch --name Test task --command drush status"

    "[ OK ] Finished Lagoon task Test task."
  )

  mocks="$(steps_run "setup")"
  run .vortex/tooling/src/vortex-task-custom-lagoon
  steps_run "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "task-custom-lagoon: fails when the project is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_lagoon=$(mock_command "lagoon")

  # The shipped '.env' carries a project name, and the script's own loader
  # restores the exported environment over it, so both variables are emptied.
  export LAGOON_PROJECT=""
  export VORTEX_TASK_CUSTOM_LAGOON_PROJECT=""
  export VORTEX_TASK_CUSTOM_LAGOON_BRANCH="test-branch"
  export VORTEX_TASK_CUSTOM_LAGOON_COMMAND="drush status"

  run .vortex/tooling/src/vortex-task-custom-lagoon
  assert_failure

  assert_output_contains "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_PROJECT."

  # Assert the guard stops the run before the CLI is reached. Without this, a
  # project name reaching the script from any source turns the test into a
  # live, authenticated request to Lagoon instead of a failed assertion.
  assert_equal "0" "$(mock_get_call_num "${mock_lagoon}")"

  popd >/dev/null || exit 1
}

@test "task-custom-lagoon: fails when the branch is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_lagoon=$(mock_command "lagoon")

  export LAGOON_PROJECT="test_project"
  unset VORTEX_TASK_CUSTOM_LAGOON_BRANCH
  export VORTEX_TASK_CUSTOM_LAGOON_COMMAND="drush status"

  run .vortex/tooling/src/vortex-task-custom-lagoon
  assert_failure

  assert_output_contains "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_BRANCH."
  assert_equal "0" "$(mock_get_call_num "${mock_lagoon}")"

  popd >/dev/null || exit 1
}

@test "task-custom-lagoon: fails when the command is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_lagoon=$(mock_command "lagoon")

  export LAGOON_PROJECT="test_project"
  export VORTEX_TASK_CUSTOM_LAGOON_BRANCH="test-branch"
  unset VORTEX_TASK_CUSTOM_LAGOON_COMMAND

  run .vortex/tooling/src/vortex-task-custom-lagoon
  assert_failure

  assert_output_contains "Missing required value for VORTEX_TASK_CUSTOM_LAGOON_COMMAND."
  assert_equal "0" "$(mock_get_call_num "${mock_lagoon}")"

  popd >/dev/null || exit 1
}
