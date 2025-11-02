#!/usr/bin/env bash
#
# Helpers related to Vortex common testing functionality.
#
# In some cases, shell may report platform incorrectly. Run with forced platform:
# DOCKER_DEFAULT_PLATFORM=linux/amd64 bats --tap tests/bats/test.bats
#
# shellcheck disable=SC2155,SC2119,SC2120,SC2044,SC2294
#

################################################################################
#                       BATS HOOK IMPLEMENTATIONS                              #
################################################################################

setup() {
  # The root directory of the project.
  export ROOT_DIR="$(dirname "$(dirname "$(cd "$(dirname "${BATS_TEST_DIRNAME}")/.." && pwd)")")"

  [ ! -d "${ROOT_DIR}/.vortex" ] && echo 'ERROR: The test should be run from the ".vortex" directory.' && exit 1

  ##
  ## Phase 1: Framework setup.
  ##

  # NOTE: If Docker tests fail, re-run with custom temporary directory
  # (must be pre-created): TMPDIR=${HOME}/.bats-tmp bats <testfile>'

  if [ -n "${DOCKER_DEFAULT_PLATFORM:-}" ]; then
    if [ "${BATS_VERBOSE_RUN:-}" = "1" ] || [ "${TEST_VORTEX_DEBUG:-}" = "1" ]; then
      echo "Using ${DOCKER_DEFAULT_PLATFORM} platform architecture."
    fi
  fi
  # LCOV_EXCL_STOP

  # Register a path to libraries.
  export BATS_LIB_PATH="${BATS_TEST_DIRNAME}/../../node_modules"

  # Load 'bats-helpers' library.
  ASSERT_DIR_EXCLUDE=("vortex" ".data")
  export ASSERT_DIR_EXCLUDE
  bats_load_library bats-helpers

  # Setup command mocking.
  setup_mock

  ##
  ## Phase 2: Pre-flight checks.
  ##

  # Override real secrets with test secrets.
  # For the development of the tests locally, export `TEST_` variables in your
  # shell before running the tests.
  export PACKAGE_TOKEN="${TEST_PACKAGE_TOKEN:-}"
  export VORTEX_CONTAINER_REGISTRY_USER="${TEST_VORTEX_CONTAINER_REGISTRY_USER:-}"
  export VORTEX_CONTAINER_REGISTRY_PASS="${TEST_VORTEX_CONTAINER_REGISTRY_PASS:-}"

  # The installer reference to use for tests.
  export TEST_INSTALLER_REF="${TEST_INSTALLER_REF:-main}"

  # Preflight checks.
  # @formatter:off
  command -v curl >/dev/null || (echo "[ERROR] curl command is not available." && exit 1)
  command -v composer >/dev/null || (echo "[ERROR] composer command is not available." && exit 1)
  command -v docker >/dev/null || (echo "[ERROR] docker command is not available." && exit 1)
  command -v ahoy >/dev/null || (echo "[ERROR] ahoy command is not available." && exit 1)
  command -v jq >/dev/null || (echo "[ERROR] jq command is not available." && exit 1)
  # @formatter:on

  ##
  ## Phase 3: Application test directories structure setup.
  ##

  # To run installation tests, several fixture directories are required.
  # They are defined and created in setup() test method.
  #
  # Root build directory where the rest of fixture directories located.
  # The "build" in this context is a place to store assets produced by the
  # installer script during the test.
  export BUILD_DIR="${BUILD_DIR:-"${BATS_TEST_TMPDIR//\/\//\/}/vortex-$(date +%s)"}"

  # Directory where the installer script is executed.
  # May have existing project files (e.g. from previous installations) or be
  # empty (to facilitate brand-new install).
  export CURRENT_PROJECT_DIR="${BUILD_DIR}/star_wars"

  # Directory where Vortex may be installed into.
  # By default, install uses ${CURRENT_PROJECT_DIR} as a destination, but we use
  # ${DST_PROJECT_DIR} to test a scenario where different destination is provided.
  export DST_PROJECT_DIR="${BUILD_DIR}/dst"

  # Directory where the installer script will be sourcing the instance of Vortex.
  # As a part of test setup, the local copy of Vortex at the last commit is
  # copied to this location. This means that during development of tests local
  # changes need to be committed.
  export LOCAL_REPO_DIR="${BUILD_DIR}/local_repo"

  # Directory where the application may store it's temporary files.
  export APP_TMP_DIR="${BUILD_DIR}/tmp"

  fixture_prepare_dir "${BUILD_DIR}"
  fixture_prepare_dir "${CURRENT_PROJECT_DIR}"
  fixture_prepare_dir "${DST_PROJECT_DIR}"
  fixture_prepare_dir "${LOCAL_REPO_DIR}"
  fixture_prepare_dir "${APP_TMP_DIR}"

  ##
  ## Phase 4: Application variables setup.
  ##

  # Isolate variables set in CI.
  unset VORTEX_DB_DOWNLOAD_SOURCE
  unset VORTEX_DB_IMAGE
  unset VORTEX_DB_DOWNLOAD_FORCE

  # Disable interactive prompts during tests.
  export AHOY_CONFIRM_RESPONSE=y
  # Disable waiting when interactive prompts are disabled durin tests.
  export AHOY_CONFIRM_WAIT_SKIP=1

  # Disable Doctor checks used on host machine.
  export VORTEX_DOCTOR_CHECK_TOOLS=0
  export VORTEX_DOCTOR_CHECK_PYGMY=0
  export VORTEX_DOCTOR_CHECK_PORT=0
  export VORTEX_DOCTOR_CHECK_SSH=0
  export VORTEX_DOCTOR_CHECK_WEBSERVER=0
  export VORTEX_DOCTOR_CHECK_BOOTSTRAP=0

  # Allow to override debug variables from environment when developing tests.
  export VORTEX_DEBUG="${TEST_VORTEX_DEBUG:-}"

  # Switch to using test demo DB.
  # Demo DB is what is being downloaded when the installer runs for the first
  # time do demonstrate downloading from CURL and importing from the DB dump
  # functionality.
  export VORTEX_INSTALLER_DEMO_DB_TEST=https://github.com/drevops/vortex/releases/download/25.4.0/db_d11_2.test.sql

  ##
  ## Phase 5: SUT files setup.
  ##

  # Copy Vortex at the last commit.
  fixture_local_repo "${LOCAL_REPO_DIR}" >/dev/null

  # Prepare global git config and ignore files required for testing cleanup scenarios.
  fixture_global_gitconfig
  fixture_global_gitignore

  ##
  ## Phase 6: Setting debug mode.
  ##

  # Print debug if "--verbose-run" is passed or TEST_VORTEX_DEBUG is set to "1".
  if [ "${BATS_VERBOSE_RUN:-}" = "1" ] || [ "${TEST_VORTEX_DEBUG:-}" = "1" ]; then
    echo "Verbose run enabled." >&3
    echo "BUILD_DIR: ${BUILD_DIR}" >&3
    export RUN_STEPS_DEBUG=1
  fi

  # Change directory to the current project directory for each test. Tests
  # requiring to operate outside of CURRENT_PROJECT_DIR (like deployment tests)
  # should change directory explicitly within their tests.
  pushd "${CURRENT_PROJECT_DIR}" >/dev/null || exit 1
}

teardown() {
  fixture_global_gitignore_restore
  popd >/dev/null || cd "${ROOT_DIR}" || exit 1
}

################################################################################
#                               FIXTURES                                       #
################################################################################

# Prepare local repository from the current codebase.
fixture_local_repo() {
  local dir="${1:-$(pwd)}"
  local do_copy_code="${2:-1}"
  local commit

  if [ "${do_copy_code:-}" -eq 1 ]; then
    fixture_prepare_dir "${dir}"
    export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1
    fixture_export_codebase "${dir}" "${ROOT_DIR}"
  fi

  git_init 0 "${dir}"
  [ "$(git config --global user.name)" = "" ] && echo "Configuring global test git user name." && git config --global user.name "Some User"
  [ "$(git config --global user.email)" = "" ] && echo "Configuring global test git user email." && git config --global user.email "some.user@example.com"
  commit=$(git_add_all_commit "Initial commit" "${dir}")

  echo "${commit}"
}

fixture_global_gitconfig() {
  git config --global init.defaultBranch >/dev/null || git config --global init.defaultBranch "main"
}

fixture_global_gitignore() {
  # Get current git global core.excludesfile setting
  current_excludes_file="$(git config --global core.excludesfile 2>/dev/null || echo '')"

  # If no excludesfile is configured, use default location
  if [ -z "${current_excludes_file}" ]; then
    filename="${HOME}/.gitignore"
  else
    filename="${current_excludes_file}"
  fi

  if [ -f "${filename}" ]; then
    echo "Global excludes file already exists: ${filename}"
    return
  fi

  # Create new global .gitignore with standard OS and IDE temporary files
  cat <<EOT >"${filename}"
##
## Temporary files generated by various OSs and IDEs
##
Thumbs.db
._*
.DS_Store
.idea
.idea/*
*.sublime*
.project
.netbeans
.vscode
.vscode/*
nbproject
nbproject/*
EOT

  # Set the global git excludes file only if it wasn't already configured
  if [ -z "${current_excludes_file}" ]; then
    git config --global core.excludesfile "${filename}"
    echo "Configured git to use global excludes file: ${filename}"
  fi

  echo "Created global excludes file: ${filename}"
}

fixture_global_gitignore_restore() {
  filename=${HOME}/.gitignore
  filename_backup="${filename}".bak
  [ -f "${filename_backup}" ] && cp "${filename_backup}" "${filename}"
  [ -f "/tmp/git_config_global_exclude" ] && git config --global core.excludesfile "$(cat /tmp/git_config_global_exclude)"
}

fixture_ssh_key_prepare() {
  export HOME="${BUILD_DIR}"
  export SSH_KEY_FIXTURE_DIR="${BUILD_DIR}/.ssh"
  fixture_prepare_dir "${SSH_KEY_FIXTURE_DIR}"
}

fixture_ssh_key() {
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa" >/dev/null
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_TEST" >/dev/null
}

fixture_ssh_key_with_suffix() {
  local suffix="${1:-TEST}"
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" >/dev/null
}

fixture_docker_config_file() {
  fixture_prepare_dir "${BUILD_DIR}/.docker"
  touch "${BUILD_DIR}/.docker/config.json"
  echo "{\"auths\": {\"${1:-docker.io}\": {\"auth\": \"bXl1c2VybmFtZTpteXBhc3N3b123\"}}}" >"${BUILD_DIR}/.docker/config.json"
}

fixture_robo() {
  export HOME="${BUILD_DIR}"
  fixture_prepare_dir "${HOME}/.composer/vendor/bin"
  touch "${HOME}/.composer/vendor/bin/robo"
  chmod +x "${HOME}/.composer/vendor/bin/robo"

  # Also create a mock for git-artifact
  touch "${HOME}/.composer/vendor/bin/git-artifact"
  chmod +x "${HOME}/.composer/vendor/bin/git-artifact"
}

################################################################################
#                               UTILITIES                                      #
################################################################################

git_init() {
  local allow_receive_update="${1:-0}"
  local dir="${2:-$(pwd)}"

  assert_not_git_repo "${dir}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init >/dev/null

  if [ "${allow_receive_update:-}" -eq 1 ]; then
    git --work-tree="${dir}" --git-dir="${dir}/.git" config receive.denyCurrentBranch updateInstead >/dev/null
  fi
}

git_add() {
  local file="${1}"
  local dir="${2:-$(pwd)}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add "${dir}/${file}" >/dev/null
}

git_add_force() {
  local file="${1}"
  local dir="${2:-$(pwd)}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add -f "${dir}/${file}" >/dev/null
}

git_commit() {
  local message="${1}"
  local dir="${2:-$(pwd)}"

  assert_git_repo "${dir}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" >/dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_add_all_commit() {
  local message="${1}"
  local dir="${2:-$(pwd)}"

  assert_git_repo "${dir}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" add -A
  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" >/dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

##
# Creates a wrapper script for a globally available binary.
#
# Creates a wrapper script for a globally available binary in a specified
# directory and filename.
# This allows scripts that reference binaries in the specified directory to use
# the global command.
#
# Parameters:
#   - path_with_bin
#     The full path where the wrapper should be created
#     (e.g., "vendor/bin/custom_drush").
#   - global_bin (optional)
#     The name of the global binary for which the wrapper is being created.
#     Defaults to the bin name from path_with_bin if not provided.
#
# Usage:
#   create_global_command_wrapper "vendor/bin/custom_drush"  # uses "custom_drush" as global_bin
#   create_global_command_wrapper "vendor/bin/custom_drush" "drush"  # uses "drush" as global_bin
create_global_command_wrapper() {
  local path_with_bin="${1}"
  local global_bin="${2:-$(basename "${path_with_bin}")}"
  mkdir -p "$(dirname "${path_with_bin}")"
  cat <<EOL >"${path_with_bin}"
#!/bin/bash
${global_bin} "\$@"
EOL
  chmod +x "${path_with_bin}"
}
