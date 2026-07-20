#!/usr/bin/env bats
##
# Unit tests for provision-30-search-index.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Provision search index: default flow in development environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Get environment.
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    # Indexing: reset tracker and index.
    "@drush -y search-api:reset-tracker"
    "@drush -y search-api:index"

    # Expected output.
    "Started search indexing operations."
    "Environment: local"
    "Search indexing skip: 0"
    "Resetting search index tracker."
    "Reset search index tracker."
    "Running search indexing."
    "Completed search indexing."
    "Finished search indexing operations."

    # Not expected.
    "- Skipped search indexing. DRUPAL_SEARCH_INDEX_SKIP is set to 1."
    "- Skipped search indexing in non-development environment."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/provision-30-search-index.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision search index: skip via variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  export DRUPAL_SEARCH_INDEX_SKIP=1

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # local"

    "Started search indexing operations."
    "Environment: local"
    "Search indexing skip: 1"
    "Skipped search indexing. DRUPAL_SEARCH_INDEX_SKIP is set to 1."

    "- Resetting search index tracker."
    "- Running search indexing."
    "- Finished search indexing operations."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/provision-30-search-index.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Provision search index: non-development environment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  rm ./.env && touch ./.env

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # prod"

    "Started search indexing operations."
    "Environment: prod"
    "Search indexing skip: 0"
    "Skipped search indexing in non-development environment."
    "Finished search indexing operations."

    "- Resetting search index tracker."
    "- Running search indexing."
    "- Completed search indexing."
  )

  mocks="$(run_steps "setup")"

  run ./scripts/provision-30-search-index.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
