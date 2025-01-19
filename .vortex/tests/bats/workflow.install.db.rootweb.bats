#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Workflow: DB-driven, custom webroot" {
  prepare_sut "Starting DB-driven WORKFLOW with custom webroot tests in build directory ${BUILD_DIR}" "rootdoc"

  assert_ahoy_download_db

  assert_ahoy_build "rootdoc"
  assert_gitignore "" "rootdoc"

  assert_ahoy_cli

  assert_env_changes

  assert_ahoy_drush

  assert_ahoy_info "rootdoc"

  assert_ahoy_export_db

  assert_ahoy_lint "rootdoc"

  assert_ahoy_test "rootdoc" "1"

  assert_ahoy_fei "rootdoc"

  assert_ahoy_fe "rootdoc"

  assert_ahoy_reset "rootdoc"

  assert_ahoy_reset_hard "rootdoc"
}
