#!/usr/bin/env bats
#
# Init tests.
#

load test_helper
load test_helper_drupaldev

@test "Install: empty directory; no Deployment, Acquia, Lagoon, FTP and Dependabot integrations" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=0
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=0
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; all integrations" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no deployment" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=0
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Acquia integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Lagoon integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=0
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no FTP integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=0
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=Y

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_dependabot "${CURRENT_PROJECT_DIR}"
}

@test "Install: empty directory; no Dependabot integration" {
  export DRUPALDEV_OPT_PRESERVE_DEPLOYMENT=Y
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y
  export DRUPALDEV_OPT_PRESERVE_LAGOON=Y
  export DRUPALDEV_OPT_PRESERVE_FTP=Y
  export DRUPALDEV_OPT_PRESERVE_DEPENDABOT=0

  run_install
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  install_dependencies_stub "${CURRENT_PROJECT_DIR}"

  assert_files_present_common "${CURRENT_PROJECT_DIR}"
  assert_files_present_deployment "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_acquia "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_lagoon "${CURRENT_PROJECT_DIR}"
  assert_files_present_integration_ftp "${CURRENT_PROJECT_DIR}"
  assert_files_present_no_integration_dependabot "${CURRENT_PROJECT_DIR}"
}
