#!/usr/bin/env bats
#
# Init tests.
#

load test_helper
load test_helper_init

@test "Install: empty directory" {
  debug "empty directory"
}

@test "Install: empty directory, project name" {
  debug "empty directory, project name"
}

@test "Install: existing project" {
  debug "existing project (name will be read from existing .env)"
}
