#!/usr/bin/env bats
#
# Init tests.
#

load _helper
load _helper_drevops

@test "Install: empty directory; no Deployment, Acquia, Lagoon, FTP and dependencies.io integrations" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=0
  export DREVOPS_OPT_PRESERVE_ACQUIA=0
  export DREVOPS_OPT_PRESERVE_LAGOON=0
  export DREVOPS_OPT_PRESERVE_FTP=0
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=0

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_no_integration_dependenciesio
}

@test "Install: empty directory; all integrations" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=Y
  export DREVOPS_OPT_PRESERVE_ACQUIA=Y
  export DREVOPS_OPT_PRESERVE_LAGOON=Y
  export DREVOPS_OPT_PRESERVE_FTP=Y
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_dependenciesio
}

@test "Install: empty directory; no deployment" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=0
  export DREVOPS_OPT_PRESERVE_ACQUIA=Y
  export DREVOPS_OPT_PRESERVE_LAGOON=Y
  export DREVOPS_OPT_PRESERVE_FTP=Y
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_no_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_dependenciesio
}

@test "Install: empty directory; no Acquia integration" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=Y
  export DREVOPS_OPT_PRESERVE_ACQUIA=0
  export DREVOPS_OPT_PRESERVE_LAGOON=Y
  export DREVOPS_OPT_PRESERVE_FTP=Y
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_dependenciesio
}

@test "Install: empty directory; no Lagoon integration" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=Y
  export DREVOPS_OPT_PRESERVE_ACQUIA=Y
  export DREVOPS_OPT_PRESERVE_LAGOON=0
  export DREVOPS_OPT_PRESERVE_FTP=Y
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_no_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_integration_dependenciesio
}

@test "Install: empty directory; no FTP integration" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=Y
  export DREVOPS_OPT_PRESERVE_ACQUIA=Y
  export DREVOPS_OPT_PRESERVE_LAGOON=Y
  export DREVOPS_OPT_PRESERVE_FTP=0
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=Y

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_files_present_integration_dependenciesio
}

@test "Install: empty directory; no dependencies.io integration" {
  export DREVOPS_OPT_PRESERVE_DEPLOYMENT=Y
  export DREVOPS_OPT_PRESERVE_ACQUIA=Y
  export DREVOPS_OPT_PRESERVE_LAGOON=Y
  export DREVOPS_OPT_PRESERVE_FTP=Y
  export DREVOPS_OPT_PRESERVE_DEPENDENCIESIO=0

  run_install
  assert_git_repo

  install_dependencies_stub

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_integration_ftp
  assert_files_present_no_integration_dependenciesio
}
