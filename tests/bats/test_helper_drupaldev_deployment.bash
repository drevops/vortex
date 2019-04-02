#!/usr/bin/env bash
#
# Helpers related to Drupal-Dev deployment testing functionality.
#

assert_deployment_files_present(){
  local dir="${1:-.}"
  local has_custom_profile="${2:-0}"

  assert_dir_not_exists "${dir}"/.circleci
  assert_dir_not_exists "${dir}"/.data
  assert_dir_not_exists "${dir}"/.docker
  assert_dir_not_exists "${dir}"/.github
  assert_dir_not_exists "${dir}"/.gitignore.deployment
  assert_dir_not_exists "${dir}"/node_modules
  assert_dir_not_exists "${dir}"/patches
  assert_dir_not_exists "${dir}"/screenshots
  assert_dir_not_exists "${dir}"/scripts
  assert_dir_not_exists "${dir}"/tests
  assert_file_not_exists "${dir}"/.ahoy.yml
  assert_file_not_exists "${dir}"/.dockerignore
  assert_file_not_exists "${dir}"/.editorconfig
  assert_file_not_exists "${dir}"/.env
  assert_file_not_exists "${dir}"/.eslintrc.json
  assert_file_not_exists "${dir}"/.lagoon.yml
  assert_file_not_exists "${dir}"/.sass-lint.yml
  assert_file_not_exists "${dir}"/behat.yml
  assert_file_not_exists "${dir}"/composer.json
  assert_file_not_exists "${dir}"/composer.lock
  assert_file_not_exists "${dir}"/dependencies.yml
  assert_file_not_exists "${dir}"/docker-compose.yml
  assert_file_not_exists "${dir}"/Gruntfile.js
  assert_file_not_exists "${dir}"/LICENSE
  assert_file_not_exists "${dir}"/package.json
  assert_file_not_exists "${dir}"/package-lock.json
  assert_file_not_exists "${dir}"/phpcs.xml
  assert_file_not_exists "${dir}"/README.md

  assert_dir_exists "${dir}"/vendor

  if [ "${has_custom_profile}" -eq 1 ]; then
    # Site profile present.
    assert_dir_exists "${dir}"/docroot/profiles/star_wars_profile
    assert_file_exists "${dir}"/docroot/profiles/star_wars_profile/star_wars_profile.info
  fi

  # Site core module present.
  assert_dir_exists "${dir}"/docroot/sites/all/modules/custom/star_wars_core
  assert_file_exists "${dir}"/docroot/sites/all/modules/custom/star_wars_core/star_wars_core.info
  assert_file_exists "${dir}"/docroot/sites/all/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists "${dir}"/docroot/sites/all/modules/custom/star_wars_core/star_wars_core.module

  # Site theme present.
  assert_dir_exists "${dir}"/docroot/sites/all/themes/custom/star_wars
  assert_file_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/.gitignore
  assert_file_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/star_wars.info
  assert_file_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/template.php

  # Core files present.
  assert_file_exists "${dir}/docroot/.editorconfig"
  assert_file_exists "${dir}/docroot/.htaccess"
  assert_file_exists "${dir}/docroot/index.php"
  assert_file_exists "${dir}/docroot/robots.txt"
  assert_file_exists "${dir}/docroot/update.php"

  # Settings files present.
  assert_file_exists "${dir}"/docroot/sites/default/settings.php
  assert_file_not_exists "${dir}"/docroot/sites/default/settings.generated.php
  assert_file_not_exists "${dir}"/docroot/sites/default/default.local.settings.php
  assert_file_not_exists "${dir}"/docroot/sites/default/local.settings.php
  assert_file_not_exists "${dir}"/docroot/sites/default/default.settings.php

  # Only minified compiled CSS present.
  assert_file_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/build/css/star_wars.css
  assert_dir_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/scss
  assert_dir_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/css

  # Only minified compiled JS exists.
  assert_file_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains "${dir}"/docroot/sites/all/themes/custom/star_wars/build/js/star_wars.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.star_wars"
  assert_file_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/build/js/star_wars.js
  assert_dir_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/js

  # Other source asset files do not exist.
  assert_dir_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/fonts
  assert_dir_not_exists "${dir}"/docroot/sites/all/themes/custom/star_wars/images
}

provision_site(){
  local dir="${1:-.}"

  pushd "${dir}" > /dev/null || exit 1

  assert_files_not_present_common "${dir}"

  step "Initialise the project with the default settings"
  # Preserve demo configuration used for this test. This is to make sure that
  # the test does not rely on external private assets (demo is still using
  # public database specified in DEMO_DB_TEST variable).
  export DRUPALDEV_REMOVE_DEMO=0

  run_install

  assert_files_present_common "${dir}"
  assert_git_repo "${dir}"

  # Point demo database to the test database.
  echo "DEMO_DB=$(ahoy getvar \$DEMO_DB_TEST)" >> .env.local

  # Special treatment for cases where volumes are not mounted from the host.
  if [ "${VOLUMES_MOUNTED}" != "1" ] ; then
  sed -i -e "/###/d" docker-compose.yml
  assert_file_not_contains docker-compose.yml "###"
  sed -i -e "s/##//" docker-compose.yml
  assert_file_not_contains docker-compose.yml "##"
  fi

  step "Add all files to new git repo"
  git_add_all_commit "${dir}" "Init Drupal-Dev config"

  # In this test, the database is downloaded from public gist specified in
  # DEMO_DB_TEST variable.
  step "Download the database"
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  step "Build project"
  # shellcheck disable=SC2015
  docker network prune -f > /dev/null && docker network inspect amazeeio-network > /dev/null || docker network create amazeeio-network
  ahoy down
  ahoy up -- --build --force-recreate >&3
  sync_to_host

  popd > /dev/null || exit 1
}
