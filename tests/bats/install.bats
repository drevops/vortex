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
  assert_contains "nothing to commit, working tree clean" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"
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
  assert_contains "nothing to commit, working tree clean" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"
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

  run_install

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
  assert_contains "nothing to commit, working tree clean" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"

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
  assert_contains "nothing to commit, working tree clean" "$(git --work-tree=${CURRENT_PROJECT_DIR} --git-dir=${CURRENT_PROJECT_DIR}/.git status)"
}

@test "Install: empty directory; no Acquia, Lagoon and FTP integrations" {
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; all integrations" {
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Acquia integration" {
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Lagoon integration" {
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no FTP integration" {
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
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
  printf 'Star Wars\n\n\n\n\n\n\n\n\n' | run_install "--interactive"

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
