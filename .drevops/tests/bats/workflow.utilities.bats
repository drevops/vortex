#!/usr/bin/env bats
#
# Utilities.
#

# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.workflow.bash

@test "Local Ahoy commands" {
  prepare_sut "Starting utilities tests in build directory ${BUILD_DIR}"

  step "Run ahoy local commands"

  substep "Assert calling local commands without local file does not throw error"
  run ahoy local
  assert_success
  assert_output_not_contains "[fatal]"
  assert_output_contains ".ahoy.local.yml does not exist."
  assert_output_contains "Copy .ahoy.local.example.yml to .ahoy.local.yml and rerun this command."

  substep "Assert calling local commands with local file path specified and file is present works correctly"
  cp ".ahoy.local.example.yml" ".ahoy.local.yml"
  run ahoy local help
  assert_success
  assert_output_contains "Custom local commands"
  assert_output_not_contains "[fatal]"
  assert_output_not_contains ".ahoy.local.yml does not exist."
  assert_output_not_contains "Copy .ahoy.local.example.yml to .ahoy.local.yml and rerun this command."

  substep "Assert calling local commands with local file path specified and file is present and file return non-zero exit code"

  echo >>".ahoy.local.yml"
  echo "  mylocalcommand:" >>".ahoy.local.yml"
  echo "    cmd: |" >>".ahoy.local.yml"
  echo "      echo 'expected failure'" >>".ahoy.local.yml"
  echo "      exit 1" >>".ahoy.local.yml"

  run ahoy local mylocalcommand
  assert_failure
  assert_output_contains "expected failure"
  assert_output_not_contains "[fatal]"
  assert_output_not_contains ".ahoy.local.yml does not exist."
  assert_output_not_contains "Copy .ahoy.local.example.yml to .ahoy.local.yml and rerun this command."
}

@test "Doctor info" {
  prepare_sut "Starting utilities tests in build directory ${BUILD_DIR}"
  step "Run ahoy doctor info"

  run ahoy doctor info
  assert_success
  assert_output_contains "System information report"
  assert_output_contains "Operating system"
  assert_output_contains "Docker"
  assert_output_contains "Docker Compose"
  assert_output_contains "Pygmy"
  assert_output_contains "Ahoy"
}

@test "GitHub labels" {
  prepare_sut "Starting utilities tests in build directory ${BUILD_DIR}"

  step "Run ahoy github-labels"

  export GITHUB_TOKEN="${TEST_GITHUB_TOKEN}"

  # Use "drevops/scaffold-destination" as an example GitHub project.
  run ahoy github-labels drevops/scaffold-destination
  assert_success
  assert_output_not_contains "ERROR"

  run curl https://github.com/drevops/scaffold-destination/labels

  assert_output_contains ">AUTOMERGE<"
  assert_output_contains "Pull request has been approved and set to automerge"
  assert_output_contains ">CONFLICT<"
  assert_output_contains "Pull request has a conflict that needs to be resolved before it can be merged"
  assert_output_contains ">DO NOT MERGE<"
  assert_output_contains "Do not merge this pull request"
  assert_output_contains ">Do not review<"
  assert_output_contains "Do not review this pull request"
  assert_output_contains ">Needs review<"
  assert_output_contains "Pull request needs a review from assigned developers"
  assert_output_contains ">Questions<"
  assert_output_contains "Pull request has some questions that need to be answered before further review can progress"
  assert_output_contains ">Ready for test<"
  assert_output_contains "Pull request is ready for manual testing"
  assert_output_contains ">Ready to be merged<"
  assert_output_contains "Pull request is ready to be merged (assigned after testing is complete)"
  assert_output_contains ">Requires more work<"
  assert_output_contains "Pull request was reviewed and reviver(s) asked to work further on the pull request"
  assert_output_contains ">URGENT<"
  assert_output_contains "Pull request needs to be urgently reviewed"
  assert_output_contains ">dependencies<"
  assert_output_contains "Pull request was raised automatically by a dependency bot"

  assert_output_not_contains ">bug<"
  assert_output_not_contains ">duplicate<"
  assert_output_not_contains ">enhancement<"
  assert_output_not_contains ">help wanted<"
  assert_output_not_contains ">good first issue<"
  assert_output_not_contains ">invalid<"
  assert_output_not_contains ">question<"
  assert_output_not_contains ">wontfix<"
}
