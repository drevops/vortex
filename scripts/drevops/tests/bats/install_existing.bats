#!/usr/bin/env bats
#
# Test installation into existing directory.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Install into existing: non-git-project; custom files; custom files preserved" {
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  run_install_quiet

  assert_files_present

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by DrevOps is preserved.
  assert_file_exists ".docker/test2.txt"
}

@test "Install into existing: non-git project; has current version; git repo created and custom files preserved" {
  run_install_quiet
  rm -Rf .git >/dev/null
  assert_not_git_repo

  # Assert files at current version.
  assert_files_present

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  unset DREVOPS_INIT_REPO
  run_install_quiet

  # Assert that a directory became a git repository.
  assert_git_repo

  # Assert no changes were made.
  assert_files_present

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by DrevOps is preserved.
  assert_file_exists ".docker/test2.txt"
}

@test "Install into existing: git project; has current version; no changes should be introduced and custom files preserved" {
  # Populate current dir with a project at current version.
  run_install_quiet

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  # Add all files to git repo.
  git_add_all_commit "Second commit"

  run_install_quiet

  # Assert no changes were made.
  assert_files_present
  assert_git_repo

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by DrevOps is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean
}

@test "Install into existing: git project; has modified version; use override flag; should have changes to committed files" {
  # Populate current dir with a project at current version.
  run_install_quiet

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  # Modify DrevOps files.
  echo "SOMEVAR=\"someval\"" >> .env

  git_add ".env"
  # Add all files to git repo.
  git_add_all_commit "Second commit"

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "Existing committed files will be modified."

  # Assert no changes were made.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by DrevOps is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert changes were introduced, since DrevOps files have overridden
  # existing files.
  assert_not_contains "nothing to commit, working tree clean" "$(git status)"
  assert_contains "modified:   .env" "$(git status)"
  assert_file_not_contains ".env" "SOMEVAR=\"someval\""
}

@test "Install into existing: git project; no DrevOps; adding DrevOps and updating DrevOps" {
  # Add custom files
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  git_init
  # Exclude one of the files that will not be excluded during installation.
  mktouch ".git/info/exclude"
  echo ".eslintrc.json" >> ".git/info/exclude"

  # Add all files to git repo.
  git_add_all_commit "First commit"
  assert_git_repo

  run_install_quiet
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  git_add_all_commit "Init DrevOps"

  # Assert that custom file preserved.
  assert_file_exists "test1.txt"
  # Assert that custom file in a directory used by DrevOps is preserved.
  assert_file_exists ".docker/test2.txt"

  # Assert no changes were introduced.
  assert_git_clean

  # Releasing new version of DrevOps.
  echo "# Some change to docker-compose" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  echo "# Some change to non-required file" >> "${LOCAL_REPO_DIR}/docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json"
  git_add "docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json" "${LOCAL_REPO_DIR}"
  git_commit "New version of DrevOps" "${LOCAL_REPO_DIR}"

  # Run install to update to the latest DrevOps version.
  run_install_quiet
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Assert that committed file was updated.
  assert_file_contains "docker-compose.yml" "# Some change to docker-compose"
  # Assert that excluded file was updated.
  assert_file_contains "${LOCAL_REPO_DIR}/docroot/sites/all/themes/custom/your_site_theme/.eslintrc.json" "# Some change to non-required file"

  # Assert changes to the repo are present.
  assert_git_not_clean
}

@test "Install into existing: custom files, not including readme; discovery; quiet" {
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

  install_dependencies_stub

  assert_files_present
  assert_git_repo
}

@test "Install into existing: custom files, including custom readme; discovery; quiet" {
  echo "some random content" >> "README.md"
  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

  install_dependencies_stub

  assert_files_present
  assert_git_repo
}

@test "Install into existing: custom files, including DrevOps's readme; discovery; quiet" {
  fixture_readme

  touch "test1.txt"
  # File resides in directory that is included in DrevOps when initialised.
  mkdir -p ".docker"
  touch ".docker/test2.txt"

  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "It looks like DrevOps is already installed into this project"

  install_dependencies_stub

  # Only common files will be present since we faked the readme file. The
  # discovering mechanism will remove integrations etc.
  assert_files_present_common
  assert_git_repo
}

@test "Install into existing: previously installed project, including correct readme; discovery; quiet" {
  # Populate current dir with a project at current version.
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

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
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "It looks like DrevOps is already installed into this project"

  assert_files_present_common
  assert_git_repo

  # Assert no changes were introduced.
  assert_git_clean
}

@test "Install into existing: previously installed project, including updated .env.local; discovery; quiet" {
  # Populate current dir with a project at current version.
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

  # Assert files at current version.
  assert_files_present
  assert_git_repo

  install_dependencies_stub

  # Add all files to git repo.
  git_add_all_commit "Second commit"
  # Remove all non-committed files.
  git reset --hard

  assert_files_present_common "star_wars" "StarWars"

  # Add a change to .env.local.
  echo "some random content" >> ".env.local"
  assert_file_contains ".env.local" "some random content"

  # Run the install again.
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_contains "It looks like DrevOps is already installed into this project"

  assert_files_present_common
  assert_git_repo

  # Assert that .env.local has not been changed.
  assert_file_contains ".env.local" "some random content"
}
