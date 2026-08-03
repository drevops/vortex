#!/usr/bin/env bats
##
# Unit tests for provision-10-enable-dev-modules.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision development modules: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Development modules.
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"

    # Expected output.
    "Started development modules operations."
    "Environment: local"
    "Installing Single Directory Component development tools."
    "Installed Single Directory Component development tools."
    "Installing Devel module."
    "Installed Devel module."
    "Finished development modules operations."

    # Not expected.
    "- Skipped installing development modules in production environment."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # devops"

    # Expected output.
    "Started development modules operations."
    "Environment: devops"
    "Skipped installing development modules in production environment."

    # Not expected.
    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Finished development modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: production environment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # prod"

    # Expected output.
    "Started development modules operations."
    "Environment: prod"
    "Skipped installing development modules in production environment."

    # Not expected.
    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Finished development modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
