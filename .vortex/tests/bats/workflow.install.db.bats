#!/usr/bin/env bats
#
# Workflows using different types of install source.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Workflow: DB-driven" {
  prepare_sut "Starting DB-driven WORKFLOW tests in build directory ${BUILD_DIR}"

  assert_ahoy_download_db

  assert_ahoy_build
  assert_gitignore

  assert_solr

  assert_ahoy_cli

  assert_env_changes

  assert_timezone

  assert_ahoy_composer

  assert_ahoy_drush

  assert_ahoy_info

  assert_ahoy_container_logs

  assert_ahoy_login

  assert_ahoy_export_db

  assert_ahoy_lint

  assert_ahoy_test

  assert_ahoy_fei

  assert_ahoy_fe

  assert_ahoy_debug

  # Run this test as a last one to make sure that there is no concurrency issues
  # with enabled Redis.
  assert_redis

  assert_ahoy_reset

  assert_ahoy_reset_hard
}
