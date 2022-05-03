#!/usr/bin/env bats
#
# Test installation into empty directory.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Variables" {
  assert_contains "drevops" "${BUILD_DIR}"
}

@test "Install into empty directory" {
  run_install_quiet

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: DST_DIR from an argument" {
  run_install_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: DST_DIR from env variable" {
  export DST_DIR="${DST_PROJECT_DIR}"
  run_install_quiet

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: DREVOPS_PROJECT from env variable" {
  export DREVOPS_PROJECT="the_matrix"

  run_install_quiet

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix" "tm" "Tm"
  assert_git_repo
}

@test "Install into empty directory: DREVOPS_PROJECT from .env file" {
  echo "DREVOPS_PROJECT=\"the_matrix\"" > ".env"

  run_install_quiet

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix" "tm" "Tm"
  assert_git_repo
}

@test "Install into empty directory: install from specific commit" {
  run_install_quiet
  assert_files_present
  assert_git_repo

  # Releasing 2 new versions of DrevOps.
  echo "# Some change to docker-compose at commit 1" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  commit1=$(git_commit "New version 1 of DrevOps" "${LOCAL_REPO_DIR}")

  echo "# Some change to docker-compose at commit 2" >> "${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  git_commit "New version 2 of DrevOps" "${LOCAL_REPO_DIR}"

  # Requiring bespoke version by commit.
  echo DREVOPS_COMMIT="${commit1}">>.env
  run_install_quiet
  assert_git_repo
  assert_output_contains "This will install DrevOps into your project at commit"
  assert_output_contains "Downloading DrevOps"
  assert_output_contains "at ref \"${commit1}\""

  assert_files_present
  assert_file_contains "docker-compose.yml" "# Some change to docker-compose at commit 1"
  assert_file_not_contains "docker-compose.yml" "# Some change to docker-compose at commit 2"
}

@test "Install into empty directory: empty directory; no local ignore" {
  run_install_quiet
  assert_files_present
  assert_git_repo

  assert_file_not_contains ".git/info/exclude" ".ahoy.yml"
}

@test "Install into empty directory: interactive" {
  answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # download_db_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: interactive; override; should override changed committed file and have no changes" {
  echo "SOMEVAR=\"someval\"" >> .env

    answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # download_db_type
    "nothing" # download_db_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"

  assert_files_present
  assert_git_repo

  assert_file_not_contains ".env" "SOMEVAR="
}

@test "Install into empty directory: quiet; should NOT show that DrevOps was previously installed" {
  output=$(run_install_quiet)
  assert_output_contains "WELCOME TO DREVOPS QUIET INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: interactive; should show that DrevOps was previously installed" {
    answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # fresh_install
    "nothing" # download_db_type
    "nothing" # download_db_source
    "nothing" # database_store_type
    "nothing" # deploy_type
    "nothing" # preserve_ftp
    "nothing" # preserve_acquia
    "nothing" # preserve_lagoon
    "nothing" # preserve_dependenciesio
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_output_not_contains "It looks like DrevOps is already installed into this project"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory; DrevOps badge version set" {
  export DREVOPS_VERSION="9.x-1.2.3"

  run_install_quiet

  # Assert that DrevOps version was replaced.
  assert_file_contains "README.md" "https://github.com/drevops/drevops/tree/9.x-1.2.3"
  assert_file_contains "README.md" "badge/DrevOps-9.x--1.2.3-blue.svg"
}

@test "Install into empty directory; db from curl, storage is database import" {
  export DREVOPS_DB_DOWNLOAD_SOURCE=curl

  run_install_quiet

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE="
}

@test "Install into empty directory; db from curl; storage is Docker image" {
  export DREVOPS_DB_DOWNLOAD_SOURCE=curl

  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x

  run_install_quiet

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
}

@test "Install into empty directory; db from Docker image; storage is Docker image" {
  export DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry
  export DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x

  run_install_quiet

  assert_file_contains ".env" "DREVOPS_DB_DOWNLOAD_SOURCE=docker_registry"
  assert_file_not_contains ".env" "DREVOPS_DB_DOWNLOAD_CURL_URL="
  assert_file_contains ".env" "DREVOPS_DB_DOCKER_IMAGE=drevops/drevops-mariadb-drupal-data-demo-9.x"
}

@test "Install into empty directory; DrevOps scripts are not modified" {
  run_install_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds"
  assert_git_repo "${DST_PROJECT_DIR}"

  assert_dirs_equal "${LOCAL_REPO_DIR}/scripts" "${DST_PROJECT_DIR}/scripts"
}

@test "Install into empty directory; Images are not modified" {
  run_install_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds"
  assert_git_repo "${DST_PROJECT_DIR}"

  assert_files_equal "${LOCAL_REPO_DIR}/tests/behat/fixtures/image.jpg" "${DST_PROJECT_DIR}/tests/behat/fixtures/image.jpg"
}
