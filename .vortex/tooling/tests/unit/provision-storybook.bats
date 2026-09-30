#!/usr/bin/env bats
##
# Unit tests for the 'scripts/provision-50-storybook.sh' script.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision Storybook: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  unset DRUPAL_STORYBOOK_SKIP
  export DRUPAL_THEME=your_site_theme

  create_global_command_wrapper "vendor/bin/drush"

  mkdir -p "./web/themes/custom/your_site_theme/node_modules/.bin"
  touch "./web/themes/custom/your_site_theme/node_modules/.bin/storybook"
  chmod +x "./web/themes/custom/your_site_theme/node_modules/.bin/storybook"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    # Stories and application build.
    "@drush -y storybook:generate-all-stories --omit-server-url --force"
    "@npm --prefix=./web/themes/custom/your_site_theme run storybook-build"

    # Expected output.
    "Started Storybook operations."
    "Environment: local"
    "Storybook skip: 0"
    "Generating stories."
    "Generated stories."
    "Building the Storybook application."
    "Built the Storybook application."
    "Finished Storybook operations."

    # Not expected.
    "- Installing Storybook module."
    "- Skipped Storybook operations. DRUPAL_STORYBOOK_SKIP is set to 1."
    "- Skipped Storybook operations in non-development environment."
    "- Skipped building the Storybook application: theme dependencies are not installed."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-50-storybook.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision Storybook: skip via variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  export DRUPAL_STORYBOOK_SKIP=1
  export DRUPAL_THEME=your_site_theme

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "Started Storybook operations."
    "Environment: local"
    "Storybook skip: 1"
    "Skipped Storybook operations. DRUPAL_STORYBOOK_SKIP is set to 1."

    "- Generating stories."
    "- Building the Storybook application."
    "- Finished Storybook operations."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-50-storybook.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision Storybook: stage environment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  unset DRUPAL_STORYBOOK_SKIP
  export DRUPAL_THEME=your_site_theme

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # stage"

    "Started Storybook operations."
    "Environment: stage"
    "Storybook skip: 0"
    "Skipped Storybook operations in non-development environment."
    "Finished Storybook operations."

    "- Generating stories."
    "- Building the Storybook application."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-50-storybook.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision Storybook: production environment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  unset DRUPAL_STORYBOOK_SKIP
  export DRUPAL_THEME=your_site_theme

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # prod"

    "Started Storybook operations."
    "Environment: prod"
    "Storybook skip: 0"
    "Skipped Storybook operations in non-development environment."
    "Finished Storybook operations."

    "- Generating stories."
    "- Building the Storybook application."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-50-storybook.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision Storybook: theme dependencies are not installed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  unset DRUPAL_STORYBOOK_SKIP
  export DRUPAL_THEME=your_site_theme

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\Core\Site\Settings::get('environment'); # local"

    "@drush -y storybook:generate-all-stories --omit-server-url --force"

    "Started Storybook operations."
    "Environment: local"
    "Generated stories."
    "Skipped building the Storybook application: theme dependencies are not installed."
    "Finished Storybook operations."

    "- Building the Storybook application."
  )

  mocks="$(steps_run "setup")"

  run ./scripts/provision-50-storybook.sh
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
