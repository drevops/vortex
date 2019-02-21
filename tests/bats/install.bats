#!/usr/bin/env bats
#
# Init tests.
#

load test_helper
load test_helper_drupaldev

@test "Install: empty directory" {
  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; DST_DIR as argument" {
  run_install "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install: empty directory; DST_DIR from env variable" {
  export DST_DIR="${DST_PROJECT_DIR}"
  run_install

  assert_files_present "${DST_PROJECT_DIR}"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install: empty directory; PROJECT from env variable" {
  export PROJECT="the_matrix"
  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; PROJECT from .env file" {
  echo "PROJECT=\"the_matrix\"" > "${CURRENT_PROJECT_DIR}/.env"

  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; PROJECT from .env.local file" {
  # Note that .env file should exist in order to read from .env.local.
  echo "PROJECT=\"star_wars\"" > "${CURRENT_PROJECT_DIR}/.env"
  echo "PROJECT=\"the_matrix\"" > "${CURRENT_PROJECT_DIR}/.env.local"

  run_install

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: directory with custom files" {
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

@test "Install: existing non-git project; current version" {
  # Populate current dir with a project at current version.
  export DRUPALDEV_INIT_REPO=0
  run_install
  assert_not_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"

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

@test "Install: existing git project; current Drupal-Dev version" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  # Add all files to git repo.
  git_add_all "${CURRENT_PROJECT_DIR}" "Second commit"

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

@test "Install: existing git project; modified Drupal-Dev version" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

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
  git_add_all "${CURRENT_PROJECT_DIR}" "Second commit"

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

@test "Install: existing git project; modified Drupal-Dev version; use override" {
  # Populate current dir with a project at current version.
  run_install

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

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
  git_add_all "${CURRENT_PROJECT_DIR}" "Second commit"

  echo "DRUPALDEV_ALLOW_OVERRIDE=1" >> "${CURRENT_PROJECT_DIR}/.env.local"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "ATTENTION! RUNNING IN UPDATE MODE"

  # Assert no changes were made.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

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

@test "Install: existing git project; no Drupal-Dev; adding Drupal-Dev and updating Drupal-Dev" {
  # Add custom files
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  git_init "${CURRENT_PROJECT_DIR}"

  # Add all files to git repo.
  git_add_all "${CURRENT_PROJECT_DIR}" "First commit"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

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

  # Assert that non-committed file was updated.
  assert_file_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose"
  # Assert that committed file was not updated.
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.circleci/config.yml" "# Some change to ci config"
  # Assert no changes to the repo.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Deployment, Acquia, Lagoon, FTP and dependencies.io integrations" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=0
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=0
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; all integrations" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no deployment" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=0
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Acquia integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Lagoon integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no FTP integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=0
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no dependencies.io integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDENCIESIO=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_dependenciesio "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; install Drupal-Dev from specific commit" {
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
  echo "DRUPALDEV_COMMIT=${commit1}" >> "${CURRENT_PROJECT_DIR}/.env.local"
  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_file_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose at commit 1"
  assert_file_not_contains "${CURRENT_PROJECT_DIR}/docker-compose.yml" "# Some change to docker-compose at commit 2"
}

@test "Install: empty directory; interactive mode" {
  output=$(printf 'Star Wars\n\n\n\n\n\n\n\n\n\n\n' | run_install "--interactive")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; interactive mode; override" {
  echo "SOMEVAR=\"someval\"" >> "${CURRENT_PROJECT_DIR}/.env"
  echo "DRUPALDEV_ALLOW_OVERRIDE=1" >> "${CURRENT_PROJECT_DIR}/.env.local"

  output=$(printf 'Star Wars\n\n\n\n\n\n\n\n\n\n\n' | run_install "--interactive")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "ATTENTION! RUNNING IN UPDATE MODE"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no local ignore" {
  export DRUPALDEV_ALLOW_USE_LOCAL_IGNORE=0

  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_file_not_contains "${CURRENT_PROJECT_DIR}/.git/info/exclude" ".ahoy.yml"
}

@test "Install: empty directory; discovery; silent" {
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed for this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; discovery; interactive" {
  output=$(printf 'Star Wars\n\n\n\n\n\n\n\n\n\n\n' | run_install "--interactive")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed for this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: existing custom files, not including readme; discovery; silent" {
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed for this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: existing custom files, including custom readme; discovery; silent" {
  echo "some random content" >> "${CURRENT_PROJECT_DIR}/README.md"
  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed for this project"

  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: existing custom files, including Drupal-Dev's readme; discovery; silent" {
  fixture_readme "${CURRENT_PROJECT_DIR}"

  touch "${CURRENT_PROJECT_DIR}/test1.txt"
  # File resides in directory that is included in Drupal-Dev when initialised.
  mkdir -p "${CURRENT_PROJECT_DIR}/.docker"
  touch "${CURRENT_PROJECT_DIR}/.docker/test2.txt"

  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed for this project"

  # Only common files will be present since we faked the readme file. The
  # discovering mechanism will remove integrations etc.
  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"
}

@test "Install: previously installed project, including correct readme; discovery; silent" {
  # Populate current dir with a project at current version.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_not_contains "It looks like Drupal-Dev is already installed for this project"

  # Assert files at current version.
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Add all files to git repo.
  git_add_all "${CURRENT_PROJECT_DIR}" "Second commit"
  # Remove all non-committed files.
  rm "${CURRENT_PROJECT_DIR}"/.git/info/exclude
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git reset --hard
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git clean -f -d
  assert_git_clean "${CURRENT_PROJECT_DIR}"
  assert_files_not_present_common "${CURRENT_PROJECT_DIR}" "star_wars" 1

  # Run the install again.
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "It looks like Drupal-Dev is already installed for this project"

    # Only common files will be present since we faked the readme file. The
  # discovering mechanism will remove integrations etc.
  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Assert no changes were introduced.
  assert_git_clean "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty dir; proceed switch; silent" {
  export DRUPALDEV_PROCEED=0
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty dir; proceed switch; interactive" {
  export DRUPALDEV_PROCEED=0
  output=$(printf 'Star Wars\n\n\n\n\n\n\n\n\n\n\n' | run_install "--interactive")
  assert_output_contains "WELCOME TO DRUPAL-DEV INTERACTIVE INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"
  assert_files_not_present_common "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty dir; discovering; silent; defaults" {
  export DRUPALDEV_PROCEED=0
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          Star wars"
  assert_output_contains "Machine name:                  star_wars"
  assert_output_contains "Organisation:                  Star wars Org"
  assert_output_contains "Organisation machine name:     star_wars_org"
  assert_output_contains "Module prefix:                 star_wars"
  assert_output_contains "Theme name:                    star_wars"
  assert_output_contains "URL:                           star_wars.com"

  assert_output_contains "Deployment:                    Enabled"
  assert_output_contains "Acquia integration:            Enabled"
  assert_output_contains "Lagoon integration:            Enabled"
  assert_output_contains "dependencies.io integration:   Enabled"
  assert_output_contains "Remove Drupal-Dev comments:    Yes"
}

@test "Install: empty dir; discovering; silent; overrides" {
  # Create readme file to pretend that Drupal-ev was installed.
  fixture_readme "${CURRENT_PROJECT_DIR}"

  fixture_composerjson "${CURRENT_PROJECT_DIR}" "My awesome site" "my_a_site" "Best org" "the_best_org"
  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/modules/custom/some_custom_module
  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/modules/custom/another_custom_core
  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/modules/custom/yetanother_custom_core

  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/themes/custom/anothertheme
  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/themes/custom/yetanothertheme

  mkdir -p "${CURRENT_PROJECT_DIR}"/docroot/sites/default
  echo "  \$config['stage_file_proxy.settings']['origin'] = 'http://www.example.com/';" > "${CURRENT_PROJECT_DIR}"/docroot/sites/default/settings.php

  echo "#;<DRUPAL-DEV" > "${CURRENT_PROJECT_DIR}"/1.txt

  export DRUPALDEV_PROCEED=0
  output=$(run_install)
  assert_output_contains "WELCOME TO DRUPAL-DEV SILENT INSTALLER"
  assert_output_contains "Aborting project installation. No files were changed"

  assert_output_contains "Name:                          My awesome site"
  assert_output_contains "Machine name:                  my_a_site"
  assert_output_contains "Organisation:                  Best org"
  assert_output_contains "Organisation machine name:     the_best_org"
  assert_output_contains "Module prefix:                 another_custom"
  assert_output_contains "Theme name:                    anothertheme"
  assert_output_contains "URL:                           www.example.com"

  assert_output_contains "Deployment:                    Disabled"
  assert_output_contains "Acquia integration:            Disabled"
  assert_output_contains "Lagoon integration:            Disabled"
  assert_output_contains "dependencies.io integration:   Disabled"
  assert_output_contains "Remove Drupal-Dev comments:    No"
}
