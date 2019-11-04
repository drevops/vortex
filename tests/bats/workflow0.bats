#!/usr/bin/env bats
#
# DB-driven workflow.
#
# Due to test speed efficiency, all assertions ran withing a single test.

load _helper
load _helper_drupaldev

@test "Workflow: DB-driven" {
  DRUPAL_VERSION=${DRUPAL_VERSION:-8}
  VOLUMES_MOUNTED=${VOLUMES_MOUNTED:-1}

  assert_not_empty "${DRUPAL_VERSION}"
  assert_not_empty "${VOLUMES_MOUNTED}"

  debug "==> Starting DB-driven WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

  assert_files_not_present_common

  step "Initialise the project with default settings"
  # Preserve demo configuration used for this test. This is to make sure that
  # the test does not rely on external private assets (demo is still using
  # public database specified in DEMO_DB_TEST variable).
  export DRUPALDEV_REMOVE_DEMO=0
  # Remove Acquia integration as we are using DEMO configuration.
  export DRUPALDEV_OPT_PRESERVE_ACQUIA=0
  # Run default install
  run_install

  assert_files_present_common
  assert_files_present_no_fresh_install
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp
  assert_git_repo

  # Point demo database to the test database.
  echo "DEMO_DB=$(ahoy getvar \$DEMO_DB_TEST)" >> .env.local

  step "Add all Drupal-Dev files to new git repo"
  git_add_all_commit "Init Drupal-Dev config"

  step "Create untracked file manually"
  touch untracked_file.txt
  assert_file_exists untracked_file.txt

  step "Create IDE config file"
  mkdir -p .idea
  touch .idea/idea_file.txt
  assert_file_exists .idea/idea_file.txt

  #
  # Preparation complete - start actual user actions testing.
  #

  step "Build without downloaded DB"
  run ahoy build
  assert_failure
  assert_output_contains "Unable to find database dump file"

  step "Download the database"
  # In this test, the database is downloaded from public gist specified in
  # DEMO_DB_TEST variable.
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  step "Build project"
  ahoy build >&3
  sync_to_host

  assert_file_exists .data/db.sql

  # Assert the presence of files from the default configuration.
  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp

  # Assert that lock files were created.
  assert_file_exists "composer.lock"
  assert_file_exists "package-lock.json"

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

  step "Enable development settings"
  assert_file_not_exists docroot/sites/default/settings.local.php
  assert_file_not_exists docroot/sites/default/services.local.yml
  assert_file_exists docroot/sites/default/default.settings.local.php
  assert_file_exists docroot/sites/default/default.services.local.yml
  cp docroot/sites/default/default.settings.local.php docroot/sites/default/settings.local.php
  cp docroot/sites/default/default.services.local.yml docroot/sites/default/services.local.yml
  assert_file_exists docroot/sites/default/settings.local.php
  assert_file_exists docroot/sites/default/services.local.yml

  step "Commit fully configured project"
  git_add_all_commit "Commit fully built project"
  # Assert that scaffold files were added to the git repository.
  assert_git_file_is_tracked docroot/.editorconfig
  assert_git_file_is_tracked docroot/.eslintignore
  assert_git_file_is_tracked docroot/.gitattributes
  assert_git_file_is_tracked docroot/.htaccess
  assert_git_file_is_tracked docroot/autoload.php
  assert_git_file_is_tracked docroot/index.php
  assert_git_file_is_tracked docroot/robots.txt
  assert_git_file_is_tracked docroot/update.php
  # Assert that lock files were added to the git repository.
  assert_git_file_is_tracked "composer.lock"
  assert_git_file_is_tracked "package-lock.json"
  # Assert that generated files were not added to the git repository.
  assert_git_file_is_not_tracked "docroot/sites/default/settings.generated.php"
  assert_git_file_is_not_tracked ".data/db.sql"
  # Assert that local settings were not added to the git repository.
  assert_git_file_is_not_tracked "docroot/sites/default/settings.local.php"
  assert_git_file_is_not_tracked "docroot/sites/default/services.local.yml"
  assert_git_file_is_not_tracked ".env.local"
  assert_git_file_is_not_tracked "docker-compose.override.yml"
  # Assert that built assets were not added to the git repository.
  assert_git_file_is_not_tracked "docroot/themes/custom/star_wars/build/css/star_wars.min.css"
  assert_git_file_is_not_tracked "ocroot/themes/custom/star_wars/build/js/star_wars.js"

  step "Run ClI command"
  run ahoy cli "echo Test from inside of the container"
  assert_success
  assert_output_not_contains "Containers are not running."
  assert_output_contains "Test from inside of the container"

  step "Run Drush command"
  run ahoy drush st
  assert_success
  assert_output_not_contains "Containers are not running."

  step "Run site info"
  run ahoy info
  assert_success
  assert_output_contains "Project                  : star_wars"
  assert_output_contains "Site local URL           : http://star-wars.docker.amazee.io"
  assert_output_contains "Path to project          : /app"
  assert_output_contains "Path to docroot          : /app/docroot"
  assert_output_contains "DB host                  : mariadb"
  assert_output_contains "DB username              : drupal"
  assert_output_contains "DB password              : drupal"
  assert_output_contains "DB port                  : 3306"
  assert_output_contains "DB port on host          :"
  assert_output_contains "Solr port on host        :"
  assert_output_contains "Livereload port on host  :"
  assert_output_contains "Mailhog URL              : http://mailhog.docker.amazee.io/"
  assert_output_contains "Xdebug                   : Disabled"
  assert_output_not_contains "Containers are not running."

  step "Show Docker logs"
  run ahoy logs
  assert_success
  assert_output_not_contains "Containers are not running."

  step "Generate one-time login link"
  run ahoy login
  assert_success
  assert_output_not_contains "Containers are not running."

  step "Export DB"
  run ahoy export-db "mydb.sql"
  assert_success
  assert_output_not_contains "Containers are not running."
  assert_file_exists ".data/mydb.sql"

  #
  # Lint code.
  #

  step "Lint code"
  run ahoy lint
  assert_success
  assert_output_not_contains "Containers are not running."

  step "Assert that lint failure bypassing works"
  echo "\$a=1;" >> docroot/modules/custom/star_wars_core/star_wars_core.module
  echo ".abc{margin: 0px;}" >> docroot/themes/custom/star_wars/scss/components/_layout.scss
  sync_to_container
  # Assert failure.
  run ahoy lint
  [ "${status}" -eq 1 ]
  run ahoy lint-be
  [ "${status}" -eq 1 ]
  run ahoy lint-fe
  # @todo: Fix sass-lint not returning correct exist code on warnings.
  [ "${status}" -eq 0 ]

  # Assert failure bypass.
  echo "ALLOW_LINT_FAIL=1" >> .env.local
  sync_to_container
  run ahoy lint
  [ "${status}" -eq 0 ]
  run ahoy lint-be
  [ "${status}" -eq 0 ]
  run ahoy lint-fe
  [ "${status}" -eq 0 ]
  rm .env.local

  #
  # Unit, Kernel and Functional tests.
  #

  step "Run unit tests"
  ahoy test-unit

  step "Assert that Drupal Unit test failure bypassing works"
  sed -i -e "s/assertEquals/assertNotEquals/g" docroot/modules/custom/star_wars_core/tests/src/Unit/ExampleUnitTest.php
  sync_to_container
  # Assert failure.
  run ahoy test-unit
  [ "${status}" -eq 1 ]

  # Assert failure bypass.
  echo "ALLOW_SIMPLETEST_TESTS_FAIL=1" >> .env.local
  sync_to_container
  run ahoy test-unit
  [ "${status}" -eq 0 ]
  rm .env.local

  step "Run Kernel tests"
  ahoy test-kernel

  step "Assert that Kernel test failure bypassing works"
  sed -i -e "s/assertEquals/assertNotEquals/g" docroot/modules/custom/star_wars_core/tests/src/Kernel/ExampleKernelTest.php
  sync_to_container
  # Assert failure.
  run ahoy test-kernel
  [ "${status}" -eq 1 ]

  # Assert failure bypass.
  echo "ALLOW_SIMPLETEST_TESTS_FAIL=1" >> .env.local
  sync_to_container
  run ahoy test-kernel
  [ "${status}" -eq 0 ]
  rm .env.local

  step "Run Functional tests"
  ahoy test-functional

  step "Assert that Functional test failure bypassing works"
  sed -i -e "s/assertEquals/assertNotEquals/g" docroot/modules/custom/star_wars_core/tests/src/Functional/ExampleFunctionalTest.php
  sync_to_container
  # Assert failure.
  run ahoy test-functional
  [ "${status}" -eq 1 ]

  # Assert failure bypass.
  echo "ALLOW_SIMPLETEST_TESTS_FAIL=1" >> .env.local
  sync_to_container
  run ahoy test-functional
  [ "${status}" -eq 0 ]
  rm .env.local

  #
  # BDD tests.
  #

  step "Run single Behat test"
  ahoy test-bdd tests/behat/features/homepage.feature
  sync_to_host
  assert_dir_not_empty screenshots

  step "Assert that Behat test failure bypassing works"
  echo "And I should be in the \"some-non-existing-page\" path" >> tests/behat/features/homepage.feature
  sync_to_container
  # Assert failure.
  run ahoy test-bdd tests/behat/features/homepage.feature
  [ "${status}" -eq 1 ]
  echo "ALLOW_BEHAT_FAIL=1" >> .env.local
  sync_to_container
  # Assert failure bypass.
  run ahoy test-bdd tests/behat/features/homepage.feature
  [ "${status}" -eq 0 ]
  rm .env.local

  #
  # FE assets.
  #
  step "Build FE assets for production"
  assert_file_not_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "#7e57e2"
  echo "\$color-tester: #7e57e2;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  echo "\$body-bg: \$color-tester;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  sync_to_container
  ahoy fe
  sync_to_host
  assert_file_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "background:#7e57e2"

  step "Build FE assets for development"
  assert_file_not_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "#91ea5e"
  echo "\$color-please: #91ea5e;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  echo "\$body-bg: \$color-please;" >> docroot/themes/custom/star_wars/scss/_variables.scss
  sync_to_container
  ahoy fed
  sync_to_host
  # Note that assets compiled for development are not minified (contains spaces
  # between properties and their values).
  assert_file_contains "docroot/themes/custom/star_wars/build/css/star_wars.min.css" "background: #91ea5e"

  #
  # DB re-import.
  #
  step "Re-import DB"
  rm -Rf .data/*
  # Point demo database to the test database.
  echo "DEMO_DB=$(ahoy getvar \$DEMO_DB_TEST)" >> .env.local
  echo "DB_EXPORT_BEFORE_IMPORT=1" >> .env.local
  ahoy download-db
  ahoy install-site
  assert_file_exists .data/db_export_*

  #
  # Xdebug.
  #
  step "Enable Xdebug"
  # Assert that Xdebug is disabled by default from the inside of the container.
  run ahoy cli "php -v | grep Xdebug"
  assert_failure
  # Assert info correctly shown from the outside of the container.
  run ahoy info
  assert_success
  assert_output_contains "Xdebug"
  assert_output_contains "Disabled"
  assert_output_not_contains "Enabled"
  # Enable debugging.
  run ahoy debug
  assert_success
  # Assert that the stack has restarted.
  assert_output_contains "CONTAINER ID"
  assert_output_contains "Enabled debug"
  # Assert that Xdebug is enabled from the inside of the container.
  run ahoy cli "php -v|grep Xdebug"
  assert_success
  # Assert info correctly shown from the outside of the container.
  run ahoy info
  assert_success
  assert_output_not_contains "Disabled"
  assert_output_contains "Enabled"
  # Assert that command when debugging is enabled does not restart the stack.
  run ahoy debug
  assert_success
  assert_output_not_contains "CONTAINER ID"
  assert_output_contains "Enabled debug"
  # Assert that restarting the stack does not have Xdebug enabled.
  run ahoy up
  assert_success
  # Assert that the stack has restarted.
  assert_output_contains "CONTAINER ID"
  # Assert that Xdebug is disabled from the inside of the container.
  run ahoy cli "php -v|grep Xdebug"
  assert_failure
  # Assert info correctly shown from the outside of the container.
  run ahoy info
  assert_success
  assert_output_contains "Xdebug"
  assert_output_contains "Disabled"
  assert_output_not_contains "Enabled"

  #
  # Clean.
  #
  step "Clean"
  ahoy clean
  # Assert that initial Drupal-Dev files have not been removed.
  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp

  assert_dir_not_exists docroot/modules/contrib
  assert_dir_not_exists docroot/themes/contrib
  assert_dir_not_exists vendor
  assert_dir_not_exists node_modules
  assert_dir_exists screenshots

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

  assert_git_repo

  #
  # Reset.
  #
  step "Reset"
  ahoy reset

  assert_files_present_common
  assert_files_present_deployment
  assert_files_present_no_integration_acquia
  assert_files_present_integration_lagoon
  assert_files_present_no_integration_ftp

  assert_file_exists "docroot/sites/default/settings.local.php"
  assert_file_exists "docroot/sites/default/services.local.yml"

  # Assert manually created file still exists.
  assert_file_exists untracked_file.txt
  # Assert IDE config file still exists.
  assert_file_exists .idea/idea_file.txt

  assert_dir_not_exists screenshots

  assert_git_repo
}
