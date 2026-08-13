#!/usr/bin/env bats
##
# Unit tests for provision-10-enable-dev-modules.sh
#
#
# The mock side effects expand when the mock runs, not when the step is defined,
# so they are single-quoted.
#
#shellcheck disable=SC2030,SC2031,SC2034,SC2016

load ../_helper.bash

@test "Provision development modules: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Development modules.
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"

    # Content generation. Both branches install the module with the same
    # arguments, so the side effect records the environment that distinguishes
    # them.
    '@drush -y pm:install generated_content # 0 #  # echo "${GENERATED_CONTENT_CREATE:-unset}" >./generated_content_create.txt'

    # Expected output.
    "Started development modules operations."
    "Environment: local"
    "Installing Single Directory Component development tools."
    "Installed Single Directory Component development tools."
    "Installing Devel module."
    "Installed Devel module."
    "Installing Generated content module."
    "Installed Generated content module."
    "Finished development modules operations."

    # Not expected.
    "- Skipped installing development modules in production environment."
    "- Content generation skipped."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./generated_content_create.txt" "1"

  popd >/dev/null || exit 1
}

@test "Provision development modules: content generation skip via variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DRUPAL_GENERATED_CONTENT_SKIP=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"

    # The module is installed either way; only the content is skipped.
    '@drush -y pm:install generated_content # 0 #  # echo "${GENERATED_CONTENT_CREATE:-unset}" >./generated_content_create.txt'

    "Started development modules operations."
    "Environment: local"
    "Installed Devel module."
    "Installing Generated content module."
    "Content generation skipped. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
    "Installed Generated content module."
    "Finished development modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains "./generated_content_create.txt" "unset"

  popd >/dev/null || exit 1
}

@test "Provision development modules: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # devops"

    "Started development modules operations."
    "Environment: devops"
    "Skipped installing development modules in production environment."

    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Installing Generated content module."
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

  unset DRUPAL_GENERATED_CONTENT_SKIP

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # prod"

    "Started development modules operations."
    "Environment: prod"
    "Skipped installing development modules in production environment."

    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Installing Generated content module."
    "- Finished development modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
