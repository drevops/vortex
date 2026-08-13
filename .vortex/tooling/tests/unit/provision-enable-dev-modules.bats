#!/usr/bin/env bats
##
# Unit tests for provision-10-enable-dev-modules.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision development modules: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=0

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

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
    "Fresh database: 0"
    "Content generation skip: 0"
    "Existing database detected. Skipped content generation to keep the existing content."
    "Finished development modules operations."

    # Not expected.
    "- Skipped installing development modules in production environment."
    "- Generating content."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # devops"

    # Expected output.
    "Started development modules operations."
    "Environment: devops"
    "Skipped installing development modules in production environment."

    # Not expected.
    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Generating content."
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
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # prod"

    # Expected output.
    "Started development modules operations."
    "Environment: prod"
    "Skipped installing development modules in production environment."

    # Not expected.
    "- Installing Single Directory Component development tools."
    "- Installing Devel module."
    "- Generating content."
    "- Finished development modules operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: content generation on a fresh database" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Development modules.
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"

    # Module and repository state.
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 1"
    "@drush -y php:eval print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0; # 0 # 1"

    # Generation.
    "@drush -y php:eval ini_set('memory_limit', '-1'); \Drupal\generated_content\GeneratedContentRepository::getInstance()->createEntities();"

    # Expected output.
    "Started development modules operations."
    "Environment: local"
    "Fresh database: 1"
    "Content generation skip: 0"
    "Generating content."
    "Generated content."
    "Finished development modules operations."

    # Not expected.
    "- Skipped content generation"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: content generation skip via variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DRUPAL_GENERATED_CONTENT_SKIP=1
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"

    "Started development modules operations."
    "Environment: local"
    "Fresh database: 1"
    "Content generation skip: 1"
    "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
    "Finished development modules operations."

    "- Generating content."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: content generation skip when module not enabled" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 0"

    "Started development modules operations."
    "Environment: local"
    "Skipped content generation: the Generated content module is not enabled."
    "Finished development modules operations."

    "- Generating content."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision development modules: content generation skip when content already exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y pm:install sdc_devel"
    "@drush -y pm:install devel"
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 1"
    "@drush -y php:eval print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0; # 0 # 0"

    "Started development modules operations."
    "Environment: local"
    "Skipped content generation: generated content already exists."
    "Finished development modules operations."

    "- Generating content."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-10-enable-dev-modules.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
