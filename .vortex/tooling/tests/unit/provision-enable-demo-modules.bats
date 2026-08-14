#!/usr/bin/env bats
##
# Unit tests for provision-00-enable-demo-modules.sh
#
# The environment-matching branch is covered end-to-end by 'provision.bats';
# this file covers the boundary that those scenarios do not reach.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision demo modules: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # devops"

    # Expected output.
    "Started demo modules operations."
    "Environment: devops"
    "Skipped demo modules operations in production environment."

    # Not expected.
    "- Creating the content model."
    "- Skipped creating the content model: Drupal CLI is not available."
    "- Installing contrib modules."
    "- Installing custom site modules."
    "- Finished demo modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-00-enable-demo-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
