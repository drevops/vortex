#!/usr/bin/env bats
##
# Unit tests for provision-10-example.sh
#
# The environment-matching branch is covered end-to-end by 'provision.bats';
# this file covers the boundary that those scenarios do not reach.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision example: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # devops"

    # Expected output.
    "Started example operations."
    "Environment: devops"
    "Skipped example operations in production environment."
    "Finished example operations."

    # Not expected.
    "- Running example operations in non-production environment."
    "- Creating the content model."
    "- Installing contrib modules."
    "- Installing Devel module."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/provision-10-example.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
