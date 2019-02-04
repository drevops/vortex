#!/usr/bin/env bash
#
# Init assertions.
#

setup(){
  CUR_DIR="$(pwd)"
  BUILD_DIR=${BUILD_DIR:-/tmp/drupal-dev-bats}
  DRUPAL_VERSION=${DRUPAL_VERSION:-8}

  prepare_fixture_dir "${BUILD_DIR}"
  pushd "${BUILD_DIR}" > /dev/null || exit 1
}

teardown(){
  popd > /dev/null || cd "${CUR_DIR}" || exit 1
}

init_project(){
  local input="${1}"
  # Initialise the project.
  # shellcheck disable=SC2059
  printf "$input" | ahoy init || flunk "Unable to initialise the project"
}

copy_code(){
  pushd "${CUR_DIR}" > /dev/null || exit 1
  # Copy latest commit to the build directory.
  git archive --format=tar HEAD | (cd "${BUILD_DIR}" && tar -xf -)
  popd > /dev/null || cd "${CUR_DIR}" || exit 1
}

assert_files_init_common(){
  # All Drupal-Dev own files removed.
  assert_dir_not_exists .drupal-dev
  # Stub profile removed.
  assert_dir_not_exists docroot/profiles/custom/mysite_profile
  # Stub code module removed.
  assert_dir_not_exists docroot/modules/custom/mysite_core
  # Stub theme removed.
  assert_dir_not_exists docroot/themes/custom/mysitetheme

  # Site profile created.
  assert_dir_exists docroot/profiles/custom/star_wars_profile
  assert_file_exists docroot/profiles/custom/star_wars_profile/star_wars_profile.info.yml
  # Site core module created.
  assert_dir_exists docroot/modules/custom/star_wars_core
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.info.yml
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.module
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.constants.php

  # Site theme created.
  assert_dir_exists docroot/themes/custom/star_wars
  assert_file_exists docroot/themes/custom/star_wars/js/star_wars.js
  assert_dir_exists docroot/themes/custom/star_wars/scss
  assert_file_exists docroot/themes/custom/star_wars/.gitignore
  assert_file_exists docroot/themes/custom/star_wars/star_wars.info.yml
  assert_file_exists docroot/themes/custom/star_wars/star_wars.libraries.yml
  assert_file_exists docroot/themes/custom/star_wars/star_wars.theme

  # Settings files exist.
  # @note The permissions can be 644 or 664 depending on the umask of OS. Also,
  # git only track 644 or 755.
  assert_file_exists docroot/sites/default/settings.php
  assert_file_mode docroot/sites/default/settings.php "644"

  assert_file_exists docroot/sites/default/default.settings.local.php
  assert_file_mode docroot/sites/default/default.settings.local.php "644"

  assert_file_exists docroot/sites/default/default.services.local.yml
  assert_file_mode docroot/sites/default/default.services.local.yml "644"

  # Documentation information added.
  assert_file_exists FAQs.md

  # Init command removed from Ahoy config.
  assert_file_exists .ahoy.yml
  assert_file_not_contains .ahoy.yml ".drupal-dev/init.sh"

  # Assert all stub strings were replaced.
  assert_dir_not_contains_string "mysite"
  assert_dir_not_contains_string "MYSITE"
  assert_dir_not_contains_string "mysitetheme"
  assert_dir_not_contains_string "myorg"
  assert_dir_not_contains_string "mysiteurl"
  # Assert all special comments were removed.
  assert_dir_not_contains_string "#|"
  assert_dir_not_contains_string "#<"
  assert_dir_not_contains_string "#>"
}
