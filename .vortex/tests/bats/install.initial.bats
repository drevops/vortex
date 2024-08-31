#!/usr/bin/env bats
#
# Test installation into empty directory.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Variables" {
  assert_contains "vortex" "${BUILD_DIR}"
}

@test "Install into empty directory only" {
  run_installer_quiet

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: VORTEX_INSTALL_DST_DIR is a current dir" {
  export CURRENT_PROJECT_DIR="${DST_PROJECT_DIR}"
  run_installer_quiet

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds" "Dst"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: VORTEX_INSTALL_DST_DIR from an argument" {
  run_installer_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds" "Dst"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: VORTEX_INSTALL_DST_DIR from env variable" {
  export VORTEX_INSTALL_DST_DIR="${DST_PROJECT_DIR}"
  run_installer_quiet

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds" "Dst"
  assert_git_repo "${DST_PROJECT_DIR}"
}

@test "Install into empty directory: VORTEX_PROJECT from environment variable" {
  export VORTEX_PROJECT="the_matrix"

  run_installer_quiet

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix" "tm" "Tm" "TheMatrix"
  assert_git_repo
}

@test "Install into empty directory: VORTEX_PROJECT from .env file" {
  echo 'VORTEX_PROJECT="the_matrix"' >".env"

  run_installer_quiet

  assert_files_present "${CURRENT_PROJECT_DIR}" "the_matrix" "tm" "Tm" "TheMatrix"
  assert_git_repo
}

@test "Install into empty directory: install from specific commit" {
  run_installer_quiet
  assert_files_present
  assert_git_repo

  # Releasing 2 new versions of Vortex.
  echo "# Some change to docker-compose.yml at commit 1" >>"${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  commit1=$(git_commit "New version 1 of Vortex" "${LOCAL_REPO_DIR}")

  echo "# Some change to docker-compose.yml at commit 2" >>"${LOCAL_REPO_DIR}/docker-compose.yml"
  git_add "docker-compose.yml" "${LOCAL_REPO_DIR}"
  git_commit "New version 2 of Vortex" "${LOCAL_REPO_DIR}"

  # Requiring bespoke version by commit.
  echo VORTEX_INSTALL_COMMIT="${commit1}" >>.env
  run_installer_quiet
  assert_git_repo
  assert_output_contains "This will install Vortex into your project at commit"
  assert_output_contains "Downloading Vortex"
  assert_output_contains "at ref \"${commit1}\""

  assert_files_present
  assert_file_contains "docker-compose.yml" "# Some change to docker-compose.yml at commit 1"
  assert_file_not_contains "docker-compose.yml" "# Some change to docker-compose.yml at commit 2"
}

@test "Install into empty directory: empty directory; no local ignore" {
  run_installer_quiet
  assert_files_present
  assert_git_repo

  assert_file_not_contains ".git/info/exclude" ".ahoy.yml"
}

@test "Install into empty directory: interactive" {
  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # download_db_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "nothing"   # deploy_type
    "nothing"   # preserve_ftp
    "nothing"   # preserve_acquia
    "nothing"   # preserve_lagoon
    "nothing"   # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: interactive; override; should override changed committed file and have no changes" {
  echo 'SOMEVAR="someval"' >>.env

  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # download_db_type
    "nothing"   # download_db_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "nothing"   # deploy_type
    "nothing"   # preserve_ftp
    "nothing"   # preserve_acquia
    "nothing"   # preserve_lagoon
    "nothing"   # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"

  assert_files_present
  assert_git_repo

  assert_file_not_contains ".env" "SOMEVAR="
}

@test "Install into empty directory: quiet; should NOT show that Vortex was previously installed" {
  output=$(run_installer_quiet)
  assert_output_contains "WELCOME TO VORTEX QUIET INSTALLER"
  assert_output_not_contains "It looks like Vortex is already installed into this project"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory: interactive; should show that Vortex was previously installed" {
  answers=(
    "Star wars" # name
    "nothing"   # machine_name
    "nothing"   # org
    "nothing"   # org_machine_name
    "nothing"   # module_prefix
    "nothing"   # profile
    "nothing"   # theme
    "nothing"   # URL
    "nothing"   # webroot
    "nothing"   # provision_use_profile
    "nothing"   # download_db_type
    "nothing"   # download_db_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "nothing"   # deploy_type
    "nothing"   # preserve_ftp
    "nothing"   # preserve_acquia
    "nothing"   # preserve_lagoon
    "nothing"   # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_output_not_contains "It looks like Vortex is already installed into this project"

  assert_files_present
  assert_git_repo
}

@test "Install into empty directory; Vortex badge version set" {
  export VORTEX_VERSION="1.2.3"

  run_installer_quiet

  # Assert that Vortex version was replaced.
  assert_file_contains "README.md" "https://github.com/drevops/scaffold/tree/1.2.3"
  assert_file_contains "README.md" "badge/Vortex-1.2.3-blue.svg"
}

@test "Install into empty directory; db from curl, storage is database import" {
  export VORTEX_DB_DOWNLOAD_SOURCE=curl

  run_installer_quiet

  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_CURL_URL="
}

@test "Install into empty directory; db from curl; storage is container image" {
  export VORTEX_DB_DOWNLOAD_SOURCE=curl

  export VORTEX_DB_IMAGE="drevops/drevops-mariadb-drupal-data-demo-10.x:latest"

  run_installer_quiet

  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_SOURCE=curl"
  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_CURL_URL="
  assert_file_contains ".env" "VORTEX_DB_IMAGE=drevops/drevops-mariadb-drupal-data-demo-10.x:latest"
}

@test "Install into empty directory; db from container image; storage is container image" {
  export VORTEX_DB_DOWNLOAD_SOURCE=container_registry
  export VORTEX_DB_IMAGE="drevops/drevops-mariadb-drupal-data-demo-10.x:latest"

  run_installer_quiet

  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_SOURCE=container_registry"
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_CURL_URL="
  assert_file_contains ".env" "VORTEX_DB_IMAGE=drevops/drevops-mariadb-drupal-data-demo-10.x:latest"
}

@test "Install into empty directory; Vortex scripts are not modified" {
  run_installer_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds" "Dst"
  assert_git_repo "${DST_PROJECT_DIR}"

  assert_dirs_equal "${LOCAL_REPO_DIR}/scripts/composer" "${DST_PROJECT_DIR}/scripts/composer"
  assert_dirs_equal "${LOCAL_REPO_DIR}/scripts/vortex" "${DST_PROJECT_DIR}/scripts/vortex"
}

@test "Install into empty directory; Images are not modified" {
  run_installer_quiet "${DST_PROJECT_DIR}"

  assert_files_present "${DST_PROJECT_DIR}" "dst" "ds" "Ds" "Dst"
  assert_git_repo "${DST_PROJECT_DIR}"

  assert_binary_files_equal "${LOCAL_REPO_DIR}/tests/behat/fixtures/image.jpg" "${DST_PROJECT_DIR}/tests/behat/fixtures/image.jpg"
}
