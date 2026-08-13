#!/usr/bin/env bats
##
# Unit tests for provision-15-generated-content.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision generated content: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Module and repository state.
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 1"
    "@drush -y php:eval print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0; # 0 # 1"

    # Generation.
    "@drush -y php:eval ini_set('memory_limit', '-1'); \Drupal\generated_content\GeneratedContentRepository::getInstance()->createEntities();"

    # Expected output.
    "Started content generation operations."
    "Environment: local"
    "Fresh database: 1"
    "Content generation skip: 0"
    "Generating content."
    "Generated content."
    "Finished content generation operations."

    # Not expected.
    "- Skipped content generation"
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: skip via variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DRUPAL_GENERATED_CONTENT_SKIP=1
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "Started content generation operations."
    "Environment: local"
    "Fresh database: 1"
    "Content generation skip: 1"
    "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: environment name containing a development name skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # devops"

    "Started content generation operations."
    "Environment: devops"
    "Skipped content generation in production environment."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: production environment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # prod"

    "Started content generation operations."
    "Environment: prod"
    "Skipped content generation in production environment."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: existing database skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=0

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "Started content generation operations."
    "Environment: local"
    "Fresh database: 0"
    "Existing database detected. Skipped content generation to keep the existing content."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: module not enabled skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 0"

    "Started content generation operations."
    "Environment: local"
    "Skipped content generation: the Generated content module is not enabled."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision generated content: existing generated content skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  unset DRUPAL_GENERATED_CONTENT_SKIP
  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"
    "@drush -y php:eval print \Drupal::moduleHandler()->moduleExists('generated_content'); # 0 # 1"
    "@drush -y php:eval print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0; # 0 # 0"

    "Started content generation operations."
    "Environment: local"
    "Skipped content generation: generated content already exists."

    "- Generating content."
    "- Finished content generation operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-15-generated-content.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
