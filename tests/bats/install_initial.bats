#!/usr/bin/env bats
#
# Test installation into empty directory.
#

load test_helper
load test_helper_drupaldev

@test "Variables" {
  assert_contains "drupal-dev" "${BUILD_DIR}"
}

@test "Install into empty directory" {
  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: DST_DIR as argument" {
  run_install "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: DST_DIR from env variable" {
  export DST_DIR="${DST_PROJECT_DIR}"
  run_install

  assert_files_present "${DST_PROJECT_DIR}" "dst"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: PROJECT from env variable" {
  export PROJECT="the_matrix"
  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: PROJECT from .env file" {
  echo "PROJECT=\"the_matrix\"" > "${CURRENT_PROJECT_DIR}/.env"

  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: PROJECT from .env.local file" {
  # Note that .env file should exist in order to read from .env.local.
  echo "PROJECT=\"star_wars\"" > "${CURRENT_PROJECT_DIR}/.env"
  echo "PROJECT=\"the_matrix\"" > "${CURRENT_PROJECT_DIR}/.env.local"

  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: install from specific commit" {
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Releasing 2 new versions of Drupal-Dev.
  echo "# Some change to docker-compose at commit 1" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "${LOCAL_REPO_DIR}" "docker-compose.yml"
  commit1=$(git_commit "${LOCAL_REPO_DIR}" "New version 1 of Drupal-Dev")

  echo "# Some change to docker-compose at commit 2" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "${LOCAL_REPO_DIR}" "docker-compose.yml"
  commit2=$(git_commit "${LOCAL_REPO_DIR}" "New version 2 of Drupal-Dev")

  # Requiring bespoke version by commit.
  export DRUPALDEV_COMMIT="${commit1}"
  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"
  assert_output_contains "This will install Drupal-Dev into your project at commit"
  assert_output_contains "Downloading Drupal-Dev at ref ${commit1}"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_file_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose at commit 1"
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose at commit 2"
}

@test "Install into empty directory: empty directory; no local ignore" {
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=0

  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml"
}

@test "Install into empty directory: empty directory; no exclude after existing exclude" {
  # Run installation with exclusion.
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=1
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
  assert_file_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml file below is excluded by Drupal-Dev"
  assert_file_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml"

  # Add non-Drupal-Dev file exclusion.
  echo "somefile" >> "${CURRENT_PROJECT_DIR}/.git/info/exclude"
  assert_file_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" "somefile"

  # Run installation without exclusion and assert that manually added exclusion
  # was preserved.
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=0
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml file below is excluded by Drupal-Dev"
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml"
  assert_file_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" "somefile"
}

@test "Install into empty directory: interactive" {
  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependabot
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: interactive; override; should override changed committed file and have no changes" {
  echo "SOMEVAR=\"someval\"" >> "${CURRENT_PROJECT_DIR}/.env"
  echo "DRUPALDEV_ALLOW_OVERRIDE=1" >> "${CURRENT_PROJECT_DIR}/.env.local"

  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependabot
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.env" "SOMEVAR="
  assert_file_contains "${CURRENT_PROJECT_DIR}/.env.local" "DRUPALDEV_ALLOW_OVERRIDE=1"
}

@test "Install into empty directory: silent; should show that Drupal-Dev was previously installed" {
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into empty directory: interactive; should show that Drupal-Dev was previously installed" {
  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # morh_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # preserve_deployment
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_ftp
    "nothing" # preserve_dependabot
    "nothing" # remove_drupaldev_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}
