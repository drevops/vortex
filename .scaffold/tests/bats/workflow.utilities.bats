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
  assert_success

  assert_output_contains ">BLOCKED<"
  assert_output_contains "Issue or pull request is blocked"
  assert_output_contains ">PR: AUTOMERGE<"
  assert_output_contains "Pull request has been approved and set to automerge"
  assert_output_contains ">PR: CONFLICT<"
  assert_output_contains "Pull request has a conflict that needs to be resolved before it can be merged"
  assert_output_contains ">PR: Dependencies<"
  assert_output_contains "Pull request was raised automatically by a dependency bot"
  assert_output_contains ">PR: DO NOT MERGE<"
  assert_output_contains "Do not merge this pull request"
  assert_output_contains ">PR: Do not review<"
  assert_output_contains "Do not review this pull request"
  assert_output_contains ">PR: Needs review<"
  assert_output_contains "Pull request needs a review from assigned developers"
  assert_output_contains ">PR: Ready for test<"
  assert_output_contains "Pull request is ready for manual testing"
  assert_output_contains ">PR: Ready to be merged<"
  assert_output_contains "Pull request is ready to be merged (assigned after testing is complete)"
  assert_output_contains ">PR: Requires more work<"
  assert_output_contains "Pull request was reviewed and reviver(s) asked to work further on the pull request"
  assert_output_contains ">PR: URGENT<"
  assert_output_contains "Pull request needs to be urgently reviewed"
  assert_output_contains ">State: Confirmed<"
  assert_output_contains "The issue was triaged and confirmed for development"
  assert_output_contains ">State: Done<"
  assert_output_contains "The issue is complete and waiting for a release"
  assert_output_contains ">State: In progress<"
  assert_output_contains "The issue is being worked on"
  assert_output_contains ">State: Needs more info<"
  assert_output_contains "The issue requires more information"
  assert_output_contains ">State: Needs more work<"
  assert_output_contains "The issue requires more work"
  assert_output_contains ">State: Needs triage<"
  assert_output_contains "An issue or PR has not been assessed and requires a triage"
  assert_output_contains ">State: QA<"
  assert_output_contains "The issue is in QA"
  assert_output_contains ">Type: Chore<"
  assert_output_contains "Issue is a related to a maintenance"
  assert_output_contains ">Type: Defect<"
  assert_output_contains "Issue is a defect"
  assert_output_contains ">Type: Feature<"
  assert_output_contains "Issue is a new feature request"
  assert_output_contains ">Type: Question<"
  assert_output_contains "Issue is a question"
  assert_output_contains ">UPSTREAM<"
  assert_output_contains "Issue or pull request is related to an upstream project"

  assert_output_not_contains ">bug<"
  assert_output_not_contains ">duplicate<"
  assert_output_not_contains ">enhancement<"
  assert_output_not_contains ">help wanted<"
  assert_output_not_contains ">good first issue<"
  assert_output_not_contains ">invalid<"
  assert_output_not_contains ">question<"
  assert_output_not_contains ">wontfix<"
}
