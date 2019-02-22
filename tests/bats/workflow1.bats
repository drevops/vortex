#!/usr/bin/env bats
#
# Fresh install workflow.
#

load test_helper
load test_helper_drupaldev

@test "Workflow: fresh install" {
  # @todo: Implement this.
  DRUPAL_VERSION=${DRUPAL_VERSION:-8}
  VOLUMES_MOUNTED=${VOLUMES_MOUNTED:-1}
}
