#!/usr/bin/env bats
#
# Integration tests assert that all required files are present for selected
# integrations.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Install: empty directory; none of Deployment, Acquia, Lagoon, FTP and renovatebot integrations" {
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
    "nothing"   # database_download_source
    "nothing"   # database_store_type
    "nothing"   # override_existing_db
    "none"      # deploy_type
    "no"        # preserve_ftp
    "no"        # preserve_acquia
    "no"        # preserve_lagoon
    "no"        # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_provision_use_profile
  assert_files_present_no_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot
}

@test "Install: empty directory; all integrations" {
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
    "curl"      # database_download_source
    "file"      # database_store_type
    "nothing"   # override_existing_db
    "nothing"   # deploy_type
    "y"         # preserve_ftp
    "y"         # preserve_acquia
    "y"         # preserve_lagoon
    "y"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_provision_use_profile
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot

  assert_file_contains ".lagoon.yml" "name: Download database"
  assert_file_contains ".lagoon.yml" "export VORTEX_PROVISION_OVERRIDE_DB=0"
  assert_file_not_contains ".lagoon.yml" "# Explicitly set DB overwrite flag to the value from .env file for deployments from the profile."
}

@test "Install: empty directory; deployment - code" {
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
    "curl"      # database_download_source
    "file"      # database_store_type
    "nothing"   # override_existing_db
    "artifact"  # deploy_type
    "y"         # preserve_ftp
    "y"         # preserve_acquia
    "y"         # preserve_lagoon
    "y"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_provision_use_profile
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_renovatebot

  assert_file_contains ".lagoon.yml" "name: Download database"
  assert_file_contains ".lagoon.yml" "export VORTEX_PROVISION_OVERRIDE_DB=0"
  assert_file_not_contains ".lagoon.yml" "# Explicitly set DB overwrite flag to the value from .env file for deployments from the profile."
}

@test "Install: empty directory; provision_use_profile" {
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
    "y"         # provision_use_profile
    "nothing"   # override_existing_db
    "artifact"  # deploy_type
    "n"         # preserve_ftp
    "n"         # preserve_acquia
    "n"         # preserve_lagoon
    "n"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_provision_use_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot
}

@test "Install: empty directory; provision_use_profile; Lagoon" {
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
    "y"         # provision_use_profile
    "nothing"   # override_existing_db
    "artifact"  # deploy_type
    "n"         # preserve_ftp
    "n"         # preserve_acquia
    "y"         # preserve_lagoon
    "n"         # preserve_renovatebot
    "nothing"   # preserve_doc_comments
    "nothing"   # preserve_vortex_info
  )
  output=$(run_installer_interactive "${answers[@]}")
  assert_output_contains "WELCOME TO VORTEX INTERACTIVE INSTALLER"
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_provision_use_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_renovatebot

  assert_file_not_contains ".lagoon.yml" "name: Download database"
  assert_file_not_contains ".lagoon.yml" "export VORTEX_PROVISION_OVERRIDE_DB=0"
  assert_file_contains ".lagoon.yml" "# Explicitly set DB overwrite flag to the value from .env file for deployments from the profile."
}
