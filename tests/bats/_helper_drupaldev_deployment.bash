#!/usr/bin/env bash
#
# Helpers related to Drupal-Dev deployment testing functionality.
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
  assert_dir_not_exists scripts
  assert_dir_not_exists tests
  assert_file_not_exists .ahoy.yml
  assert_file_not_exists .dockerignore
  assert_file_not_exists .editorconfig
  assert_file_not_exists .env
  assert_file_not_exists .eslintrc.json
  assert_file_not_exists .lagoon.yml
  assert_file_not_exists .sass-lint.yml
  assert_file_not_exists behat.yml
  assert_file_not_exists composer.json
  assert_file_not_exists composer.lock
  assert_file_not_exists dependencies.yml
  assert_file_not_exists docker-compose.yml
  assert_file_not_exists Gruntfile.js
  assert_file_not_exists LICENSE
  assert_file_not_exists package.json
  assert_file_not_exists package-lock.json
  assert_file_not_exists phpcs.xml
  assert_file_not_exists phpunit.xml
  assert_file_not_exists README.md

  assert_dir_exists vendor

  if [ "${has_custom_profile}" -eq 1 ]; then
    # Site profile present.
    assert_dir_exists docroot/profiles/custom/star_wars_profile
    assert_file_exists docroot/profiles/custom/star_wars_profile/star_wars_profile.info.yml
  fi

  # Site core module present.
  assert_dir_exists docroot/modules/custom/star_wars_core
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.info.yml
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists docroot/modules/custom/star_wars_core/star_wars_core.module

  # Site theme present.
  assert_dir_exists docroot/themes/custom/star_wars
  assert_file_exists docroot/themes/custom/star_wars/.gitignore
  assert_file_exists docroot/themes/custom/star_wars/star_wars.info.yml
  assert_file_exists docroot/themes/custom/star_wars/star_wars.libraries.yml
  assert_file_exists docroot/themes/custom/star_wars/star_wars.theme

  # Scaffolding files present.
  assert_file_exists "docroot/.editorconfig"
  assert_file_exists "docroot/.eslintignore"
  assert_file_exists "docroot/.gitattributes"
  assert_file_exists "docroot/.htaccess"
  assert_file_exists "docroot/autoload.php"
  assert_file_exists "docroot/index.php"
  assert_file_exists "docroot/robots.txt"
  assert_file_exists "docroot/update.php"

  # Settings files present.
  assert_file_exists docroot/sites/default/settings.php
  assert_file_not_exists docroot/sites/default/settings.generated.php:
  assert_file_not_exists docroot/sites/default/default.local.settings.php:
  assert_file_not_exists docroot/sites/default/local.settings.php:
  assert_file_not_exists docroot/sites/default/default.settings.php:

  # Only minified compiled CSS present.
  assert_file_exists docroot/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_exists docroot/themes/custom/star_wars/build/css/star_wars.css
  assert_dir_not_exists docroot/themes/custom/star_wars/scss
  assert_dir_not_exists docroot/themes/custom/star_wars/css

  # Only minified compiled JS exists.
  assert_file_exists docroot/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains docroot/themes/custom/star_wars/build/js/star_wars.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.star_wars"
  assert_file_not_exists docroot/themes/custom/star_wars/build/js/star_wars.js
  assert_dir_not_exists docroot/themes/custom/star_wars/js

  # Other source asset files do not exist.
  assert_dir_not_exists docroot/themes/custom/star_wars/fonts
  assert_dir_not_exists docroot/themes/custom/star_wars/images

  # Assert configuration dir exists.
  assert_dir_exists config/default

  popd > /dev/null || exit 1
}

provision_site(){
  local dir="${1:-$(pwd)}"

  pushd "${dir}" > /dev/null || exit 1

  assert_files_not_present_common

  step "Initialise the project with the default settings"
  # Preserve demo configuration used for this test. This is to make sure that
  # the test does not rely on external private assets (demo is still using
  # public database specified in DEMO_DB_TEST variable).
  export DRUPALDEV_REMOVE_DEMO=0

  run_install

  assert_files_present_common
  assert_git_repo

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
  git_add_all_commit "Init Drupal-Dev config" "${dir}"

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
