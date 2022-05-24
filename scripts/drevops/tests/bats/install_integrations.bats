#!/usr/bin/env bats
#
# Integration tests assert that all required files are present for selected
# integrations.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _helper_drevops

@test "Install: empty directory; none of Deployment, Acquia, Lagoon, FTP and renovatebot integrations" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # install_from_profile
    "nothing" # database_download_source
    "nothing" # database_store_type
    "nothing" # override_existing_db
    "none" # deploy_type
    "no" # preserve_ftp
    "no" # preserve_acquia
    "no" # preserve_lagoon
    "no" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_install_from_profile
  assert_files_present_no_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot
}

@test "Install: empty directory; all integrations" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # install_from_profile
    "curl" # database_download_source
    "file" # database_store_type
    "nothing" # override_existing_db
    "nothing" # deploy_type
    "y" # preserve_ftp
    "y" # preserve_acquia
    "y" # preserve_lagoon
    "y" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_install_from_profile
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot

  assert_file_contains ".lagoon.yml" "name: Download database"
  assert_file_contains ".lagoon.yml" "export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=0"
  assert_file_not_contains ".lagoon.yml" "# Deployments from UI are not able to bypass the value of"
}

@test "Install: empty directory; deployment - code" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "nothing" # install_from_profile
    "curl" # database_download_source
    "file" # database_store_type
    "nothing" # override_existing_db
    "artifact" # deploy_type
    "y" # preserve_ftp
    "y" # preserve_acquia
    "y" # preserve_lagoon
    "y" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_install_from_profile
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot

  assert_file_contains ".lagoon.yml" "name: Download database"
  assert_file_contains ".lagoon.yml" "export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=0"
  assert_file_not_contains ".lagoon.yml" "# Deployments from UI are not able to bypass the value of"
}

@test "Install: empty directory; install_from_profile" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "y" # install_from_profile
    "nothing" # override_existing_db
    "artifact" # deploy_type
    "n" # preserve_ftp
    "n" # preserve_acquia
    "n" # preserve_lagoon
    "n" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_install_from_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot
}

@test "Install: empty directory; install_from_profile; Lagoon" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "y" # install_from_profile
    "nothing" # override_existing_db
    "artifact" # deploy_type
    "n" # preserve_ftp
    "n" # preserve_acquia
    "y" # preserve_lagoon
    "n" # preserve_renovatebot
    "nothing" # preserve_doc_comments
    "nothing" # preserve_drevops_info
  )
  output=$(run_install_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO DREVOPS INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_install_from_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot

  assert_file_not_contains ".lagoon.yml" "name: Download database"
  assert_file_not_contains ".lagoon.yml" "export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=0"
  assert_file_contains ".lagoon.yml" "# Deployments from UI are not able to bypass the value of"
}
