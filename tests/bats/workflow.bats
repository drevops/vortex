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
  # DB_FTP_USER="..." DB_FTP_PASS="..." DB_FTP_HOST="..." bats tests/bats/workflow.bats --tap
  assert_not_empty "${DB_FTP_HOST}"
  assert_not_empty "${DB_FTP_USER}"
  assert_not_empty "${DB_FTP_PASS}"
  assert_not_empty "${DRUPAL_VERSION}"
  assert_not_empty "${VOLUMES_MOUNTED}"

  debug "==> Starting WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  assert_files_not_present_common "${CURRENT_PROJECT_DIR}"

  step "Initialise the project with default settings"
  # Preserve demo configuration used for this test.
  export DRUPALDEV_REMOVE_DEMO=0
  run_install
  assert_files_present "${CURRENT_PROJECT_DIR}"
  assert_git_repo "${CURRENT_PROJECT_DIR}"

  # Special treatment for cases where volumes are not mounted from the host.
  if [ "${VOLUMES_MOUNTED}" != "1" ] ; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  step "Create .env.local file"
  {
    echo FTP_HOST="${DB_FTP_HOST}";
    echo FTP_USER="${DB_FTP_USER}";
    echo FTP_PASS="${DB_FTP_PASS}";
    echo FTP_FILE="db_d${DRUPAL_VERSION}.star_wars.sql";
  } >> .env.local

  step "Add all files to new git repo"
  git_add_all "${CURRENT_PROJECT_DIR}" "Init Drupal-Dev config"

  step "Create untracked file manually"
  touch untracked_file.txt
  assert_file_exists untracked_file.txt

  step "Create IDE config file"
  mkdir -p .idea
  touch .idea/idea_file.txt
  assert_file_exists .idea/idea_file.txt

  step "Download the database"
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  step "Build project"
  ahoy build >&3
  sync_to_host

  assert_files_present "${CURRENT_PROJECT_DIR}"

  # Assert generated settings file exists.
  assert_file_exists docroot/sites/default/settings.generated.php
  # Assert only minified compiled CSS exists.
  assert_file_exists docroot/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_contains docroot/themes/custom/star_wars/build/css/star_wars.min.css "background: #7e57e2"
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

  step "Lint code"
  ahoy lint

  step "PHPUnit tests"
  ahoy test-phpunit

  step "Run single Behat test"
  ahoy test-behat tests/behat/features/homepage.feature
  sync_to_host
  assert_dir_not_empty screenshots

  step "Assert that lint failure bypassing works"
  echo "\$a=1;" >> docroot/modules/custom/star_wars_core/star_wars_core.module
  sync_to_container
  # Assert failure.
  run ahoy lint
  [ "${status}" -eq 1 ]
  # Assert failure bypass.
  echo "ALLOW_LINT_FAIL=1" >> .env.local
  sync_to_container
  run ahoy lint
  [ "${status}" -eq 0 ]

  # @todo: Add assertions for PHPunit bypass flag here.

  step "Assert that Behat test failure bypassing works"
  echo "And I should be in the \"some-non-existing-page\" path" >> tests/behat/features/homepage.feature
  sync_to_container
  # Assert failure.
  run ahoy test-behat tests/behat/features/homepage.feature
  [ "${status}" -eq 1 ]
  echo "ALLOW_BEHAT_FAIL=1" >> .env.local
  sync_to_container
  # Assert failure bypass.
  run ahoy test-behat tests/behat/features/homepage.feature
  [ "${status}" -eq 0 ]

  step "Build FE assets for production"
  assert_file_not_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "#7e57e2"
  echo "\$color-tester: #7e57e2;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  echo "\$body-bg: \$color-tester;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  sync_to_container
  ahoy fe
  sync_to_host
  debug "docroot/themes/custom/star_wars/build/css/star_wars.min.css"
  assert_file_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "background:#7e57e2"

  step "Build FE assets for development"
  assert_file_not_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "#91ea5e"
  echo "\$color-please: #91ea5e;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  echo "\$body-bg: \$color-please;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  sync_to_container
  ahoy fed
  sync_to_host
  debug "docroot/themes/custom/star_wars/build/css/star_wars.min.css"
  # Note that assets compiled for development are not minified (contains spaces
  # between properties and their values).
  assert_file_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "background: #91ea5e"

  step "Re-import DB"
  rm -Rf .data/*
  echo "DB_EXPORT_BEFORE_IMPORT=1" >> .env.local
  ahoy download-db
  ahoy install-site
  assert_file_exists .data/db_export_*

  step "Clean"
  ahoy clean
  # Assert that initial Drupal-Dev files have not been removed.
  assert_files_present "${CURRENT_PROJECT_DIR}"
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
  # Assert manually created file still exists.
  assert_file_exists untracked_file.txt
  # Assert IDE config file still exists.
  assert_file_exists .idea/idea_file.txt
  # Assert containers are not running.
  assert_containers_not_running

  step "Clean Full"
  ahoy clean-full
  assert_files_not_present_common "${CURRENT_PROJECT_DIR}" "star_wars" 1
  # Assert manually created local settings file was removed.
  assert_file_not_exists docroot/sites/default/settings.local.php
  # Assert manually created local services file was removed.
  assert_file_not_exists docroot/sites/default/services.local.yml
  # Assert manually created file still exists.
  assert_file_exists untracked_file.txt
  # Assert IDE config file still exists.
  assert_file_exists .idea/idea_file.txt

  popd > /dev/null
}
