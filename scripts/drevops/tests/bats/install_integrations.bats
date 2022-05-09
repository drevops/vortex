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
    "nothing" # fresh_install
    "nothing" # database_download_source
    "nothing" # database_store_type
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
  assert_files_present_no_fresh_install
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
    "nothing" # fresh_install
    "curl" # database_download_source
    "file" # database_store_type
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
  assert_files_present_no_fresh_install
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot
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
    "nothing" # fresh_install
    "curl" # database_download_source
    "file" # database_store_type
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
  assert_files_present_no_fresh_install
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot
}

@test "Install: empty directory; fresh_install" {
 answers=(
    "Star wars" # name
    "nothing" # machine_name
    "nothing" # org
    "nothing" # org_machine_name
    "nothing" # module_prefix
    "nothing" # profile
    "nothing" # theme
    "nothing" # URL
    "y" # fresh_install
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
  assert_files_present_fresh_install
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot
}
