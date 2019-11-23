#!/usr/bin/env bats
#
# Init tests.
#

load _helper
load _helper_drupaldev

@test "Install into existing: non-git-project; custom files; custom files preserved" {
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  run_install

  assert_files_present

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"
}

@test "Install into existing: non-git project; has current version; git repo created and custom files preserved" {
  # Populate current dir with a project at current version.
  export DRUPALDEV_INIT_REPO=0
  run_install
  assert_not_git_repo

  # Assert files at current version.
  assert_files_present

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  unset DRUPALDEV_INIT_REPO
  run_install

  # Assert that a directory became a git repository.
  assert_git_repo

  # Assert no changes were made.
  assert_files_present

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"
}

@test "Install into existing: git project; has current version; no changes should be introduced and custom files preserved" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  # Add all files to git repo.
  git_add_all_commit "Second commit"

  run_install

  # Assert no changes were made.
  assert_files_present
  assert_git_repo

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean
}

@test "Install into existing: git project; has modified version; no modified files and no changes to committed files" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  # Modify Drupal-Dev files.
  echo "SOMEVAR=\"someval\"" >> .env
  # Add all files to git repo.
  git_add_all_commit "Second commit"

  run_install

  # Assert no changes were made.
  assert_files_present

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced, since Drupal-Dev files do not override
  # existing files by default.
  assert_git_clean
  assert_file_contains ".env" "SOMEVAR=\"someval\""
}

@test "Install into existing: git project; has modified version; use override flag; should have changes to committed files" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  # Modify Drupal-Dev files.
  echo "SOMEVAR=\"someval\"" >> .env

  git_add ".env"
  # Add all files to git repo.
  git_add_all_commit "Second commit"

  echo "DRUPALDEV_ALLOW_OVERRIDE=1" >> .env

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "ATTENTION! RUNNING IN UPDATE MODE"

  # Assert no changes were made.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert changes were introduced, since Drupal-Dev files have overridden
  # existing files.
  assert_not_contains "nothing to commit, working tree clean" "$(git status)"
  assert_contains "modified:   .env" "$(git status)"
  assert_file_not_contains ".env" "SOMEVAR=\"someval\""
}

@test "Install into existing: git project; no Drupal-Dev; adding Drupal-Dev and updating Drupal-Dev" {
  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  git_init
  # Exclude one of the files that will not be excluded during installation.
  mktouch ".git/info/exclude"
  echo ".eslintrc.json" >> ".git/info/exclude"

  # Add all files to git repo.
  git_add_all_commit "First commit"
  assert_git_repo

  run_install
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  git_add_all_commit "Init Drupal-Dev"

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by Drupal-Dev is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean

  # Releasing new version of Drupal-Dev.
  echo "# Some change to docker-compose" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  echo "# Some change to non-required file" >> "${LOCAL_REPO_DIR}/docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json"
  git_add "docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json" "${LOCAL_REPO_DIR}"
  git_commit "New version of Drupal-Dev" "${LOCAL_REPO_DIR}"

  # Run install to update to the latest Drupal-Dev version.
  run_install
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Assert that committed file was not updated.
  assert_file_not_contains "docker-compose.yml" "# Some change to docker-compose"
  # Assert that excluded file was updated.
  assert_file_contains "${LOCAL_REPO_DIR}/docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json" "# Some change to non-required file"

  # Assert no changes to the repo.
  assert_git_clean
}

@test "Install into existing: custom files, not including readme; discovery; silent" {
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub

  assert_files_present
  assert_git_repo
}

@test "Install into existing: custom files, including custom readme; discovery; silent" {
  echo "some random content" >> "README.md"
  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub

  assert_files_present
  assert_git_repo
}

@test "Install into existing: custom files, including Drupal-Dev's readme; discovery; silent" {
  fixture_readme

  touch "test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed into this project"

  install_dependencies_stub

  # Only common files will be present since we faked the readme file. The
  # discovering mechanism will remove integrations etc.
  assert_files_present_common
  assert_git_repo
}

@test "Install into existing: previously installed project, including correct readme; discovery; silent" {
  # Populate current dir with a project at current version.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed into this project"

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add all files to git repo.
  git_add_all_commit "Second commit"
  # Remove all non-committed files.
  git reset --hard
  git clean -f -d
  git clean -f -d
  assert_git_clean
  assert_files_present_common "star_wars" "StarWars"

  # Run the install again.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed into this project"

  assert_files_present_common
  assert_git_repo

  # Assert no changes were introduced.
  assert_git_clean
}
