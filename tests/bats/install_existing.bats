#!/usr/bin/env bats
#
# Init tests.
#

load test_helper
load test_helper_drupaldev

@test "Install into existing: non-git-project; custom files; custom files preserved" {
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"
}

@test "Install into existing: non-git project; has current version; git repo created and custom files preserved" {
  # Populate current dir with a project at current version.
  export DRUPALDEV_INIT_REPO=0
  run_install
  assert_not_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  unset DRUPALDEV_INIT_REPO
  run_install

  # Assert that a directory became a git repository.
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert no changes were made.
  assert_files_present "${CURRENT_PROJECT_DIR}"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"
}

@test "Install into existing: git project; has current version; no changes should be introduced and custom files preserved" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Second commit"

  run_install

  # Assert no changes were made.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: git project; has modified version; no modified files and no changes to committed files" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Modify Drupal-Dev files.
  echo "SOMEVAR=\"someval\"" >> "${CURRENT_PROJECT_DIR}/.env"
  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Second commit"

  run_install

  # Assert no changes were made.
  assert_files_present "${CURRENT_PROJECT_DIR}"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Assert no changes were introduced, since Drupal-Dev files do not override
  # existing files by default.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
  assert_file_contains "${CURRENT_PROJECT_DIR}/.env" "SOMEVAR=\"someval\""
}

@test "Install into existing: git project; has modified version; use override flag; should have changes to committed files" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Modify Drupal-Dev files.
  echo "SOMEVAR=\"someval\"" >> "${CURRENT_PROJECT_DIR}/.env"

  # .env would be excluded locally - so force-add it.
  git_add_force "${CURRENT_PROJECT_DIR}" ".env"
  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Second commit"

  echo "DRUPALDEV_ALLOW_OVERRIDE=1" >> "${CURRENT_PROJECT_DIR}/.env.local"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "ATTENTION! RUNNING IN UPDATE MODE"

  # Assert no changes were made.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Assert changes were introduced, since Drupal-Dev files have overridden
  # existing files.
  assert_not_contains "nothing to commit, working tree clean" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"
  assert_contains "modified:   .env" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.env" "SOMEVAR=\"someval\""
}

@test "Install into existing: git project; no Drupal-Dev; no-exclude; adding Drupal-Dev and updating Drupal-Dev" {
  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  git_init "${CURRENT_PROJECT_DIR}"
  # Exclude one of the files that will not be excluded during installation.
  mktouch "${CURRENT_PROJECT_DIR}/.git/info/exclude"
  echo "/.eslintrc.json" >> "${CURRENT_PROJECT_DIR}/.git/info/exclude"

  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "First commit"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Commit files required to run the project.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Init Drupal-Dev"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"

  # Releasing new version of Drupal-Dev.
  echo "# Some change to docker-compose" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "${LOCAL_REPO_DIR}" "docker-compose.yml"
  echo "# Some change to non-required file" >> "${LOCAL_REPO_DIR}/.eslintrc.json"
  git_add "${LOCAL_REPO_DIR}" ".eslintrc.json"
  git_commit "${LOCAL_REPO_DIR}" "New version of Drupal-Dev"

  # Run install to update to the latest Drupal-Dev version.
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Assert that committed file was not updated.
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose"
  # Assert that excluded file was updated.
  assert_file_contains "${LOCAL_REPO_DIR}/.eslintrc.json" "# Some change to non-required file"

  # Assert no changes to the repo.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: git project; no Drupal-Dev; exclude; adding Drupal-Dev and updating Drupal-Dev" {
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=1

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  git_init "${CURRENT_PROJECT_DIR}"

  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "First commit"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Commit files required to run the project.
  git_add "${CURRENT_PROJECT_DIR}" README.md
  git_add "${CURRENT_PROJECT_DIR}" drupal-dev.sh
  git_add "${CURRENT_PROJECT_DIR}" .circleci/config.yml
  git_add "${CURRENT_PROJECT_DIR}" docroot/sites/default/settings.php
  git_add "${CURRENT_PROJECT_DIR}" docroot/sites/default/services.yml
  git_commit "${CURRENT_PROJECT_DIR}" "Init Drupal-Dev"

  # Assert that custom file preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"

  # Releasing new version of Drupal-Dev.
  echo "# Some change to docker-compose" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "${LOCAL_REPO_DIR}" "docker-compose.yml"
  echo "# Some change to ci config" >> "${LOCAL_REPO_DIR}/.circleci/config.yml"
  git_add "${LOCAL_REPO_DIR}" ".circleci/config.yml"
  git_commit "${LOCAL_REPO_DIR}" "New version of Drupal-Dev"

  # Run install to update to the latest Drupal-Dev version.
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Assert that non-committed file was updated.
  assert_file_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose"
  # Assert that committed file was not updated.
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.circleci/config.yml" "# Some change to ci config"
  # Assert no changes to the repo.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: custom files, not including readme; no-exclude; discovery; silent" {
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: custom files, not including readme; exclude; discovery; silent" {
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=1

  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: custom files, including custom readme; discovery; silent" {
  echo "some random content" >> "${CURRENT_PROJECT_DIR}/README.md"
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: custom files, including Drupal-Dev's readme; discovery; silent" {
  fixture_readme "${CURRENT_PROJECT_DIR}"

  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Only common files will be present since we faked the readme file. The
  # discovering mechanism will remove integrations etc.
  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: previously installed project, including correct readme; non-exclude; discovery; silent" {
  # Populate current dir with a project at current version.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Second commit"
  # Remove all non-committed files.
  cat "${CURRENT_PROJECT_DIR}"/.git/info/exclude
  rm "${CURRENT_PROJECT_DIR}"/.git/info/exclude
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git reset --hard
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  assert_git_clean "${CURRENT_PROJECT_DIR}"
  assert_files_present_common "${CURRENT_PROJECT_DIR}" "star_wars"

  # Run the install again.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed into this project"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install into existing: previously installed project, including correct readme; exclude; discovery; silent" {
  export DRUPALDEV_ALLOW_USE_LOCAL_EXCLUDE=1

  # Populate current dir with a project at current version.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  # Add all files to git repo.
  git_add_all_commit "${CURRENT_PROJECT_DIR}" "Second commit"
  # Remove all non-committed files.
  cat "${CURRENT_PROJECT_DIR}"/.git/info/exclude
  rm "${CURRENT_PROJECT_DIR}"/.git/info/exclude
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git reset --hard
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  assert_git_clean "${CURRENT_PROJECT_DIR}"
  assert_files_not_present_common "${CURRENT_PROJECT_DIR}" "star_wars" 1

  # Run the install again.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed into this project"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}
