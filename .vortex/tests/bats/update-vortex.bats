#!/usr/bin/env bats
#
# Test for update Vortex functionality.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Update" {
  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Vortex when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  git_init

  # Add all files to git repo.
  git_add_all_commit "First commit"
  assert_git_repo

  run_installer_quiet
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  git_add_all_commit "Init Vortex"

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Vortex is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean

  # Releasing new version of Vortex (note that installing from the local tag
  # is not supported in .vortex/installer/install; only commit is supported).
  echo "# Some change to docker-compose.yml" >>"${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  echo "# Some change to non-required file" >>"${LOCAL_REPO_DIR}/web/themes/custom/your_site_theme/.eslintrc.json"
  git_add "web/themes/custom/your_site_theme/.eslintrc.json" "${LOCAL_REPO_DIR}"
  latest_commit=$(git_commit "New version of Vortex" "${LOCAL_REPO_DIR}")

  # Override Vortex release commit in .env file.
  echo VORTEX_INSTALL_COMMIT="${latest_commit}" >>.env
  # Enforce debugging of the install script.
  export VORTEX_INSTALL_DEBUG=1
  # Override install script with currently tested one to be called from ./scripts/vortex/update-vortex.sh
  export VORTEX_INSTALLER_URL="file://${INSTALLER_CHECKOUT_DIR}/install.php"
  # shellcheck disable=SC2059
  run ahoy update-vortex
  assert_success
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Assert that committed files were updated.
  assert_file_contains "docker-compose.yml" "# Some change to docker-compose.yml"
  assert_file_contains "web/themes/custom/star_wars/.eslintrc.json" "# Some change to non-required file"

  # Assert that new changes need to be manually resolved.
  assert_git_not_clean
}
