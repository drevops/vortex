#!/usr/bin/env bats
#
# Init tests.
#

load test_helper
load test_helper_drupaldev

@test "Workflow" {
  DRUPAL_VERSION=${DRUPAL_VERSION:-8}
  VOLUMES_MOUNTED=${VOLUMES_MOUNTED:-1}

  # Safeguard for test itself. It should be ran with FTP credentials provided.
  # DB_FTP_USER="..." DB_FTP_PASS="..." DB_FTP_HOST="..." bats .drupal-dev/tests/bats/workflow.bats --tap
  assert_not_empty "${DB_FTP_HOST}"
  assert_not_empty "${DB_FTP_USER}"
  assert_not_empty "${DB_FTP_PASS}"
  assert_not_empty "${DRUPAL_VERSION}"
  assert_not_empty "${VOLUMES_MOUNTED}"

  debug "==> Starting WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  # Special treatment for cases where volumes are not mounted from the host.
  if [ "${VOLUMES_MOUNTED}" != "1" ] ; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  step "Initialise the project with default settings"
  run_install
  assert_added_files "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  step "Create .env.local file"
  {
    echo FTP_HOST="${DB_FTP_HOST}";
    echo FTP_USER="${DB_FTP_USER}";
    echo FTP_PASS="${DB_FTP_PASS}";
    echo FTP_FILE="db_d${DRUPAL_VERSION}.star_wars.sql";
  } >> .env.local

  step "Add all files to new git repo"
  git_add_all "${CURRENT_PROJECT_DIR}" "Init Drupal-Dev config"

  step "Download the database"
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  step "Build project"
  ahoy build >&3
  sync_to_host

  assert_added_files "${CURRENT_PROJECT_DIR}"

  # Assert generated settings file exists.
  assert_file_exists docroot/sites/default/settings.generated.php
  # Assert only minified compiled CSS exists.
  assert_file_exists docroot/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_contains docroot/themes/custom/star_wars/build/css/star_wars.min.css "background:#fff"
  assert_file_not_exists docroot/themes/custom/star_wars/build/css/star_wars.css
  # Assert only minified compiled JS exists.
  assert_file_exists docroot/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains docroot/themes/custom/star_wars/build/js/star_wars.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.star_wars"
  assert_file_not_exists docroot/themes/custom/star_wars/build/js/star_wars.js

  # @todo: Try moving this before test.
  sync_to_container behat.yml
  sync_to_container phpcs.xml
  sync_to_container tests
  # @todo: Add test that the correct DB was loaded (e.g. CURL and grep for page title).

  step "Enable development settings"
  assert_file_not_exists docroot/sites/default/settings.local.php
  assert_file_not_exists docroot/sites/default/services.local.yml
  assert_file_exists docroot/sites/default/default.settings.local.php
  assert_file_exists docroot/sites/default/default.services.local.yml
  cp docroot/sites/default/default.settings.local.php docroot/sites/default/settings.local.php
  cp docroot/sites/default/default.services.local.yml docroot/sites/default/services.local.yml
  assert_file_exists docroot/sites/default/settings.local.php
  assert_file_exists docroot/sites/default/services.local.yml

  step "Run generic command"
  ahoy cli "echo Test"

  step "Run drush command"
  ahoy drush st

  step "Generate one-time login link"
  ahoy login

  step "Export DB"
  ahoy export-db "mydb.sql"
  assert_file_exists ".data/mydb.sql"

  step "Run single Behat test"
  ahoy test-behat tests/behat/features/homepage.feature
  sync_to_host
  assert_dir_not_empty screenshots

  step "Build FE assets"
  echo "\$body-bg: \$color-white;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  ahoy fed
  sync_to_host
  assert_file_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "#fff"

  step "Re-import DB"
  rm -Rf .data/*
  echo "DB_EXPORT_BEFORE_IMPORT=1" >> .env.local
  ahoy download-db
  ahoy install-site
  assert_file_exists .data/db_export_*

  step "Clean"
  ahoy clean
  assert_added_files "${CURRENT_PROJECT_DIR}"
  assert_file_not_exists docroot/index.php
  assert_dir_not_exists docroot/modules/contrib
  assert_dir_not_exists docroot/themes/contrib
  assert_dir_not_exists vendor
  assert_dir_not_exists node_modules
  assert_dir_not_exists screenshots
  # Assert manually created local settings file exists.
  assert_file_exists docroot/sites/default/settings.local.php
  # Assert manually created local services file exists.
  assert_file_exists docroot/sites/default/services.local.yml
  # Assert generated settings file does not exist.
  assert_file_not_exists docroot/sites/default/settings.generated.php
  # Assert containers are not running.
  assert_containers_not_running
}

# Print step.
step(){
  debug ""
  debug "==> STEP: $1"
}

# Sync files to host in case if volumes are not mounted from host.
sync_to_host(){
  export "$(grep -v '^#' .env | xargs)"
  [ "$VOLUMES_MOUNTED" == "1" ] && return
  echo "Syncing from $(docker-compose ps -q cli) to ${BUILD_DIR}"
  docker cp -L "$(docker-compose ps -q cli)":/app/. "${BUILD_DIR}"
}

# Sync files to container in case if volumes are not mounted from host.
sync_to_container(){
  export "$(grep -v '^#' .env | xargs)"
  [ "$VOLUMES_MOUNTED" == "1" ] && return
  echo "Syncing from ${1} to $(docker-compose ps -q cli)"
  docker cp -L "${1}" "$(docker-compose ps -q cli)":/app/
}

# Assert that containers are not running.
assert_containers_not_running(){
  export "$(grep -v '^#' .env | xargs)"
  if [ -z `docker ps -q --no-trunc | grep $(docker-compose ps -q cli)` ]; then
    return 0
  else
    return 1
  fi
}
