#!/usr/bin/env bats
#
# Test for update Vortex functionality.
#
# shellcheck disable=SC2030,SC2031,SC2129

load ../_helper.bash

@test "Update" {
  substep "Add custom files"
  touch "test1.txt"
  # File resides in directory that is included in Vortex when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  substep "Init empty git repo and commit files"
  git_init
  git_add_all_commit "First commit"
  assert_git_repo

  substep "Run Vortex installer"
  run_installer_quiet
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  substep "Add all new files form Vortex and commit"
  git_add_all_commit "Init Vortex"

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Vortex is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean

  substep "Releasing new version of Vortex"
  # Installing from the local tag is not supported in .vortex/installer/installer.php.
  # Only commit is supported.
  echo "# Some change to docker-compose.yml" >>"${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  echo "# Some change to non-required file" >>"${LOCAL_REPO_DIR}/web/themes/custom/your_site_theme/.eslintrc.json"
  git_add "web/themes/custom/your_site_theme/.eslintrc.json" "${LOCAL_REPO_DIR}"
  latest_commit=$(git_commit "New version of Vortex" "${LOCAL_REPO_DIR}")

  export VORTEX_INSTALLER_TEMPLATE_REF="${latest_commit}"
  export TEST_VORTEX_VERSION="${latest_commit}"

  substep "Building Vortex installer"
  composer --working-dir="${ROOT_DIR}/.vortex/installer" install >/dev/null
  composer --working-dir="${ROOT_DIR}/.vortex/installer" build >/dev/null
  assert_file_exists "${ROOT_DIR}/.vortex/installer/build/installer.phar"
  substep "Built Vortex installer: $(php ${ROOT_DIR}/.vortex/installer/build/installer.phar --version)"

  # Override install script with the currently tested one to be called
  # from ./scripts/vortex/update-vortex.sh
  export VORTEX_INSTALLER_URL="file://${ROOT_DIR}/.vortex/installer/build/installer.phar"

  substep "Update Vortex from the template repository"
  # shellcheck disable=SC2059
  run ahoy update-vortex
  assert_success
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  substep "Assert that committed files were updated."
  assert_file_contains "docker-compose.yml" "# Some change to docker-compose.yml"
  assert_file_contains "web/themes/custom/star_wars/.eslintrc.json" "# Some change to non-required file"

  substep "Assert that new changes need to be manually resolved."
  assert_git_not_clean

  substep "Assert that installer script was removed."
  assert_file_not_exists "installer.php"
}
