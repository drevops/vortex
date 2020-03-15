#!/usr/bin/env bash
#
# Helpers related to DrevOps deployment testing functionality.
#

assert_deployment_files_present(){
  local dir="${1:-$(pwd)}"
  local has_custom_profile="${2:-0}"

  pushd "${dir}" > /dev/null || exit 1

  assert_dir_not_exists .circleci
  assert_dir_not_exists .data
  assert_dir_not_exists .docker
  assert_dir_not_exists .github
  assert_dir_not_exists .gitignore.deployment
  assert_dir_not_exists node_modules
  assert_dir_not_exists patches
  assert_dir_not_exists screenshots
  assert_dir_not_exists tests
  assert_file_not_exists .ahoy.yml
  assert_file_not_exists .dockerignore
  assert_file_not_exists .editorconfig
  assert_file_not_exists .env
  assert_file_not_exists .lagoon.yml
  assert_file_not_exists .sass-lint.yml
  assert_file_not_exists behat.yml
  assert_file_not_exists composer.json
  assert_file_not_exists composer.lock
  assert_file_not_exists dependencies.yml
  assert_file_not_exists docker-compose.yml
  assert_file_not_exists LICENSE
  assert_file_not_exists phpcs.xml
  assert_file_not_exists README.md

  assert_dir_exists scripts

  if [ "${has_custom_profile}" -eq 1 ]; then
    # Site profile present.
    assert_dir_exists docroot/profiles/star_wars_profile
    assert_file_exists docroot/profiles/star_wars_profile/star_wars_profile.info
  fi

  # Site core module present.
  assert_dir_exists docroot/sites/all/modules/custom/star_wars_core
  assert_file_exists docroot/sites/all/modules/custom/star_wars_core/star_wars_core.info
  assert_file_exists docroot/sites/all/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists docroot/sites/all/modules/custom/star_wars_core/star_wars_core.module

  # Site theme present.
  assert_dir_exists docroot/sites/all/themes/custom/star_wars
  assert_file_exists docroot/sites/all/themes/custom/star_wars/.gitignore
  assert_file_exists docroot/sites/all/themes/custom/star_wars/star_wars.info
  assert_file_exists docroot/sites/all/themes/custom/star_wars/template.php
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/Gruntfile.js
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/package.json
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/package-lock.json
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/.eslintrc.json
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/node_modules

  # Scaffolding files present.
  assert_file_exists "docroot/.htaccess"
  assert_file_exists "docroot/index.php"
  assert_file_exists "docroot/cron.php"
  assert_file_exists "docroot/install.php"
  assert_file_exists "docroot/robots.txt"

  # Settings files present.
  assert_file_exists docroot/sites/default/settings.php
  assert_file_not_exists docroot/sites/default/settings.generated.php
  assert_file_not_exists docroot/sites/default/default.settings.local.php
  assert_file_not_exists docroot/sites/default/settings.local.php
  assert_file_not_exists docroot/sites/default/default.settings.php

  # Only minified compiled CSS present.
  assert_file_exists docroot/sites/all/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/build/css/star_wars.css
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/scss
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/css

  # Only minified compiled JS exists.
  assert_file_exists docroot/sites/all/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains docroot/sites/all/themes/custom/star_wars/build/js/star_wars.min.js "!function(Drupal){\"use strict\";Drupal.behaviors.star_wars={attach:function(t){"
  assert_file_not_exists docroot/sites/all/themes/custom/star_wars/build/js/star_wars.js
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/js

  # Other source asset files do not exist.
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/fonts
  assert_dir_not_exists docroot/sites/all/themes/custom/star_wars/images

  popd > /dev/null || exit 1
}

provision_site(){
  local dir="${1:-$(pwd)}"

  pushd "${dir}" > /dev/null || exit 1

  assert_files_not_present_common

  step "Initialise the project with the default settings"

  enable_demo_db

  run_install

  assert_files_present_common
  assert_git_repo

  # Special treatment for cases where volumes are not mounted from the host.
  if [ "${VOLUMES_MOUNTED}" != "1" ] ; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  step "Add all files to new git repo"
  git_add_all_commit "Init DrevOps config" "${dir}"

  step "Build project"
  export SKIP_POST_DB_IMPORT=1
  ahoy build
  sync_to_host

  popd > /dev/null || exit 1
}
