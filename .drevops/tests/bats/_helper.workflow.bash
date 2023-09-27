#!/usr/bin/env bash
# shellcheck disable=SC2154,SC2129
#
# Helpers related to DrevOps workflow testing functionality.
#

prepare_sut() {
  step "Run SUT preparation: ${1}"
  local webroot="${2:-web}"

  DREVOPS_DRUPAL_VERSION=${DREVOPS_DRUPAL_VERSION:-9}
  DREVOPS_DEV_VOLUMES_MOUNTED=${DREVOPS_DEV_VOLUMES_MOUNTED:-1}

  assert_not_empty "${DREVOPS_DRUPAL_VERSION}"
  assert_not_empty "${DREVOPS_DEV_VOLUMES_MOUNTED}"

  assert_files_not_present_common "" "" "" "" "${webroot}"

  substep "Initialise the project with default settings"

  # Run default install
  export DREVOPS_WEBROOT="${webroot}"
  run_install_quiet

  assert_files_present_common "" "" "" "" "" "${webroot}"
  assert_files_present_no_provision_use_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia "" "" "${webroot}"
  assert_files_present_no_integration_lagoon "" "" "${webroot}"
  assert_files_present_no_integration_ftp
  assert_files_present_integration_renovatebot
  assert_git_repo

  substep "Add all DrevOps files to new git repo"
  git_add_all_commit "Init DrevOps config"

  substep "Create IDE config file"
  mkdir -p .idea
  touch .idea/idea_file.txt
  assert_file_exists .idea/idea_file.txt

  if uname -a | grep -q ARM64; then
    substep "Override local Docker Compose for ARM."
    cp docker-compose.override.default.yml docker-compose.override.yml
  fi
}

docker_remove_image() {
  docker image rm "${1}" || true
  docker image ls | grep -q -v "${1}"
}

assert_ahoy_download_db() {
  step "Run DB download"

  substep "Download the database"

  # Tests are using demo database and 'ahoy download-db' command, so we need
  # to set the CURL DB to test DB.
  #
  # Override demo database with test demo database. This is required to use
  # test assertions ("star wars") with demo database.
  #
  # Ahoy will load environment variable and it will take precedence over
  # the value in .env file.
  export DREVOPS_DB_DOWNLOAD_CURL_URL="${DREVOPS_INSTALL_DEMO_DB_TEST}"

  # Remove any previously downloaded DB dumps.
  rm -Rf .data/db.sql

  # In this test, the database is downloaded from the public URL specified in
  # DREVOPS_DB_DOWNLOAD_CURL_URL variable.
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  trim_file .env
}

assert_ahoy_build() {
  local webroot="${1:-web}"

  substep "Started project build"

  # Tests are using demo database and 'ahoy download-db' command, so we need
  # to set the CURL DB to test DB.
  #
  # Override demo database with test demo database. This is required to use
  # test assertions ("star wars") with demo database.
  #
  # Ahoy will load environment variable and it will take precedence over
  # the value in .env file.
  export DREVOPS_DB_DOWNLOAD_CURL_URL="${DREVOPS_INSTALL_DEMO_DB_TEST}"

  # Check that database file exists before build.
  db_file_exists=0
  [ -f ".data/db.sql" ] && db_file_exists=1

  # export DREVOPS_DOCKER_VERBOSE="1"

  run ahoy build
  sync_to_host

  # Assert output messages. Note that only asserting generic messages that do
  # not depend on the type of the workflow.
  assert_output_contains "Started building project"
  assert_output_contains "Removing project containers and packages available since the previous run."
  assert_output_contains "Building Docker images, recreating and starting containers."
  assert_output_contains "Installing development dependencies."
  assert_output_contains "Finished building project"

  # Assert that lock files were created.
  assert_file_exists "composer.lock"
  assert_file_exists "${webroot}/themes/custom/star_wars/package-lock.json"

  # Assert that database file preserved after build if existed before.
  if [ "${db_file_exists:-}" = "1" ]; then
    assert_file_exists .data/db.sql
  else
    assert_file_not_exists .data/db.sql
  fi

  # Assert the presence of files from the default configuration.
  assert_files_present_common "" "" "" "" "" "${webroot}"
  assert_files_present_no_provision_use_profile
  assert_files_present_deployment
  assert_files_present_no_integration_acquia "" "" "${webroot}"
  assert_files_present_no_integration_lagoon "" "" "${webroot}"
  assert_files_present_no_integration_ftp
  assert_files_present_integration_renovatebot

  # Assert only minified compiled CSS exists.
  assert_file_exists "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css"
  assert_file_not_contains "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css" "background: #7e57e2"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/build/css/star_wars.css"
  # Assert only minified compiled JS exists.
  assert_file_exists "${webroot}/themes/custom/star_wars/build/js/star_wars.min.js"
  assert_file_contains "${webroot}/themes/custom/star_wars/build/js/star_wars.min.js" '!function(Drupal){"use strict";Drupal.behaviors.star_wars'
  assert_file_not_exists "${webroot}/themes/custom/star_wars/build/js/star_wars.js"

  substep "Finished project build"
}

assert_gitignore() {
  local skip_commit="${1:-0}"
  local webroot="${2:-web}"

  step "Run .gitignore test"

  create_development_settings "${webroot}"

  if [ "${skip_commit:-}" -ne 1 ]; then
    substep "Commit fully configured project"
    git_add_all_commit "Commit fully built project"
  fi

  # Assert that scaffold files were added to the git repository.
  assert_git_file_is_tracked "${webroot}/.editorconfig"
  assert_git_file_is_tracked "${webroot}/.eslintignore"
  assert_git_file_is_tracked "${webroot}/.gitattributes"
  assert_git_file_is_tracked "${webroot}/.htaccess"
  assert_git_file_is_tracked "${webroot}/autoload.php"
  assert_git_file_is_tracked "${webroot}/index.php"
  assert_git_file_is_tracked "${webroot}/robots.txt"
  assert_git_file_is_tracked "${webroot}/update.php"
  # Assert that lock files were added to the git repository.
  assert_git_file_is_tracked "composer.lock"
  assert_git_file_is_tracked "${webroot}/themes/custom/star_wars/package-lock.json"
  assert_git_file_is_not_tracked ".data/db.sql"
  # Assert that local settings were not added to the git repository.
  assert_git_file_is_not_tracked "${webroot}/sites/default/settings.local.php"
  assert_git_file_is_not_tracked "${webroot}/sites/default/services.local.yml"
  assert_git_file_is_not_tracked "docker-compose.override.yml"
  # Assert that built assets were not added to the git repository.
  assert_git_file_is_not_tracked "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css"
  assert_git_file_is_not_tracked "${webroot}/themes/custom/star_wars/build/js/star_wars.js"

  remove_development_settings "${webroot}"
}

assert_ahoy_cli() {
  step "Run ClI command"

  run ahoy cli "echo Test from inside of the container"
  assert_success
  assert_output_not_contains "Containers are not running."
  assert_output_contains "Test from inside of the container"
}

assert_env_changes() {
  step "Update .env file and apply changes"

  # Assert that .env does not contain test values.
  assert_file_not_contains ".env" "MY_CUSTOM_VAR"
  assert_file_not_contains ".env" "my_custom_var_value"
  # Assert that test variable is not available inside of containers.
  run ahoy cli "printenv | grep -q MY_CUSTOM_VAR"
  assert_failure
  # Assert that test value is not available inside of containers.
  run ahoy cli 'echo $MY_CUSTOM_VAR | grep -q my_custom_var_value'
  assert_failure
  assert_output_not_contains "my_custom_var_value"

  # Add variable to the .env file and apply the change to container.
  add_var_to_file .env "MY_CUSTOM_VAR" "my_custom_var_value"
  ahoy up cli
  sync_to_container

  # Assert that .env contains test values.
  assert_file_contains ".env" "MY_CUSTOM_VAR"
  assert_file_contains ".env" "my_custom_var_value"
  # Assert that test variable and values are available inside of containers.
  run ahoy cli "printenv | grep MY_CUSTOM_VAR"
  assert_success
  assert_output_contains "my_custom_var_value"
  # Assert that test variable and value are available inside of containers.
  run ahoy cli 'echo $MY_CUSTOM_VAR | grep my_custom_var_value'
  assert_output_contains "my_custom_var_value"
  assert_success

  # Restore file, apply changes and assert that original behaviour has been restored.
  restore_file ".env"
  ahoy up cli
  sync_to_container

  assert_file_not_contains ".env" "MY_CUSTOM_VAR"
  assert_file_not_contains ".env" "my_custom_var_value"
  run ahoy cli "printenv | grep -q MY_CUSTOM_VAR"
  assert_failure
  run ahoy cli 'echo $MY_CUSTOM_VAR | grep my_custom_var_value'
  assert_failure
  assert_output_not_contains "my_custom_var_value"
}

assert_timezone() {
  step "Check that timezone can be applied"

  # Assert that .env contains a default value.
  assert_file_contains ".env" 'DREVOPS_TZ="Australia/Melbourne"'
  run docker compose exec cli date
  assert_output_contains "AEST"
  run docker compose exec php date
  assert_output_contains "AEST"
  run docker compose exec nginx date
  assert_output_contains "AEST"
  run docker compose exec mariadb date
  assert_output_contains "AEST"

  # Add variable to the .env file and apply the change to container.
  add_var_to_file .env "DREVOPS_TZ" '"Australia/Perth"'
  sync_to_container
  run ahoy up

  run docker compose exec cli date
  assert_output_contains "AWST"
  run docker compose exec php date
  assert_output_contains "AWST"
  run docker compose exec nginx date
  assert_output_contains "AWST"
  run docker compose exec mariadb date
  assert_output_contains "AWST"

  # Restore file, apply changes and assert that original behaviour has been restored.
  restore_file ".env"
  sync_to_container
  run ahoy up
  sleep 10
}

assert_ahoy_composer() {
  step "Run composer command"

  run ahoy composer about
  assert_success
  assert_output_contains "Composer - Dependency Manager for PHP - version 2."
  assert_output_contains "Composer is a dependency manager tracking local dependencies of your projects and libraries."
}

assert_ahoy_drush() {
  step "Run Drush command"

  run ahoy drush st
  assert_success
  assert_output_not_contains "Containers are not running."
}

assert_ahoy_info() {
  local webroot="${1:-web}"
  local db_image="${2:-}"

  step "Run site info"

  run ahoy info
  assert_success
  assert_output_contains "Project name                : star_wars"
  assert_output_contains "Docker Compose project name : star_wars"
  assert_output_contains "Site local URL              : http://star_wars.docker.amazee.io"
  assert_output_contains "Path to project             : /app"
  assert_output_contains "Path to web root            : /app/${webroot}"
  assert_output_contains "DB host                     : mariadb"
  assert_output_contains "DB username                 : drupal"
  assert_output_contains "DB password                 : drupal"
  assert_output_contains "DB port                     : 3306"
  assert_output_contains "DB port on host             :"
  if [ -n "${db_image:-}" ]; then
    assert_output_contains "DB-in-docker image          : ${db_image}"
  else
    assert_output_not_contains "DB-in-docker image          : ${db_image}"
  fi
  assert_output_contains "Solr URL on host            :"
  assert_output_contains "Mailhog URL                 : http://mailhog.docker.amazee.io/"
  assert_output_contains "Xdebug                      : Disabled ('ahoy debug' to enable)"
  assert_output_not_contains "Containers are not running."
}

assert_ahoy_docker_logs() {
  step "Show Docker logs"

  run ahoy logs
  assert_success
  assert_output_not_contains "Containers are not running."
}

assert_ahoy_login() {
  step "Generate one-time login link"

  run ahoy login
  assert_success
  assert_output_not_contains "Containers are not running."
}

assert_ahoy_export_db() {
  step "Export DB"
  file="${1:-mydb.sql}"
  run ahoy export-db "${file}"
  assert_success
  assert_output_not_contains "Containers are not running."
  sync_to_host
  assert_file_exists ".data/${file}"
}

assert_ahoy_lint() {
  local webroot="${1:-web}"

  step "Run linter checks"

  substep "Assert that lint works"
  run ahoy lint
  assert_success
  assert_output_contains "Running back-end code linter checks."
  assert_output_contains "Back-end code passed the linter checks."
  assert_output_contains "Running front-end code linter checks."
  assert_output_contains "Front-end code passed the linter checks."

  assert_ahoy_lint_be "${webroot}"

  assert_ahoy_lint_fe "${webroot}"

  substep "Assert that lint skipping works"
  add_var_to_file .env "DREVOPS_LINT_SKIP" "1"
  ahoy up cli && sync_to_container
  run ahoy lint
  assert_success
  assert_output_contains "Skipping code lint checks."
  assert_output_not_contains "Running back-end code linter checks."
  assert_output_not_contains "Running front-end code linter checks."

  restore_file .env && ahoy up cli
}

assert_ahoy_lint_be() {
  local webroot="${1:-web}"

  step "Run BE linter checks"

  substep "Assert that BE lint failure works"
  echo '$a=1;' >>"${webroot}/modules/custom/sw_core/sw_core.module"
  sync_to_container
  run ahoy lint-be
  assert_failure
  assert_output_contains "Running back-end code linter checks."
  assert_output_contains "Back-end code failed the linter checks."
  assert_output_not_contains "Back-end code passed the linter checks."

  substep "Assert that BE lint failure bypassing works"
  add_var_to_file .env "DREVOPS_LINT_BE_ALLOW_FAILURE" "1"
  ahoy up cli && sync_to_container
  run ahoy lint-be
  assert_success
  assert_output_contains "Running back-end code linter checks."
  assert_output_contains "Back-end code failed the linter checks, but failure is allowed."
  restore_file .env && ahoy up cli

  substep "Assert that BE lint tool disabling works"
  # Only testing for PHPCS, but the same should work for other tools.
  echo '$a=1;' >>"${webroot}/modules/custom/sw_core/sw_core.module"
  sync_to_container
  export DREVOPS_LINT_PHPCS_TARGETS=""
  run ahoy lint-be
  assert_success
  assert_output_contains "Running back-end code linter checks."
  assert_output_contains "Back-end code passed the linter checks."
  restore_file .env && ahoy up cli
}

assert_ahoy_lint_fe() {
  local webroot="${1:-web}"

  step "Run FE linter checks"

  substep "Assert that FE lint failure works for npm lint"
  echo ".abc{margin: 0px;}" >>"${webroot}/themes/custom/star_wars/scss/components/_test.scss"
  sync_to_container
  run ahoy lint-fe
  assert_failure
  assert_output_contains "Running front-end code linter checks."
  assert_output_contains "Running theme npm lint."
  assert_output_not_contains "Running Twigcs."
  assert_output_contains "Front-end code failed the linter checks."
  assert_output_not_contains "Front-end code passed the linter checks."
  restore_file .env && ahoy up cli
  rm -f "${webroot}/themes/custom/star_wars/scss/components/_test.scss"
  ahoy cli rm -f "${webroot}/themes/custom/star_wars/scss/components/_test.scss"
  sync_to_container

  substep "Assert that FE lint failure works for Twigcs"
  mkdir -p "${webroot}/modules/custom/sw_core/templates/block"
  mkdir -p "${webroot}/themes/custom/star_wars/templates/block"
  echo "{{ set a='a' }}" >>"${webroot}/modules/custom/sw_core/templates/block/test1.twig"
  echo "{{ set b='b' }}" >>"${webroot}/themes/custom/star_wars/templates/block/test2.twig"
  sync_to_container
  run ahoy lint-fe
  assert_failure
  assert_output_contains "Running front-end code linter checks."
  assert_output_contains "Running theme npm lint."
  assert_output_contains "Running Twigcs."
  assert_output_contains "violation(s) found"
  assert_output_contains "Front-end code failed the linter checks."
  assert_output_not_contains "Front-end code passed the linter checks."
  restore_file .env && ahoy up cli

  substep "Assert that FE lint failure bypassing works"
  add_var_to_file .env "DREVOPS_LINT_FE_ALLOW_FAILURE" "1"
  ahoy up cli && sync_to_container
  run ahoy lint-fe
  assert_success
  assert_output_contains "Running front-end code linter checks."
  assert_output_contains "Front-end code failed the linter checks, but failure is allowed."
  assert_output_not_contains "Front-end code passed the linter checks."
  restore_file .env && ahoy up cli

  substep "Assert that FE lint tool disabling works"
  # Only testing for Twigcs, but the same should work for other tools.
  mkdir -p "${webroot}/modules/custom/sw_core/templates/block"
  mkdir -p "${webroot}/themes/custom/star_wars/templates/block"
  echo "{{ set a='a' }}" >>"${webroot}/modules/custom/sw_core/templates/block/test1.twig"
  echo "{{ set b='b' }}" >>"${webroot}/themes/custom/star_wars/templates/block/test2.twig"
  sync_to_container
  export DREVOPS_LINT_TWIGCS_TARGETS=""
  run ahoy lint-fe
  assert_success
  assert_output_contains "Running front-end code linter checks."
  assert_output_contains "Front-end code passed the linter checks."
  restore_file .env && ahoy up cli
}

assert_ahoy_test() {
  local webroot="${1:-web}"
  local is_fast="${2:-0}"

  step "Run tests"

  assert_ahoy_test_unit "${webroot}"

  assert_ahoy_test_kernel "${webroot}"

  assert_ahoy_test_functional "${webroot}"

  if [ "${is_fast:-}" == "1" ]; then
    assert_ahoy_test_bdd_fast "${webroot}"
  else
    assert_ahoy_test_bdd "${webroot}"
  fi

  substep "Assert that test skipping works"
  add_var_to_file .env "DREVOPS_TEST_SKIP" "1"
  ahoy up cli && sync_to_container

  run ahoy test
  assert_success
  assert_output_contains "Skipping running tests."
  assert_output_not_contains "Running Unit tests."
  assert_output_not_contains "Running Kernel tests."
  assert_output_not_contains "Running Functional tests."
  assert_output_not_contains "Running BDD tests."

  restore_file .env && ahoy up cli
}

assert_ahoy_test_unit() {
  local webroot="${1:-web}"

  step "Run Drupal Unit tests"

  substep "Run all Unit tests"
  run ahoy test-unit
  assert_success
  assert_output_contains "Running Unit tests."
  # Scripts.
  assert_output_contains "OK (5 tests, 5 assertions)"
  # Modules.
  assert_output_contains "OK (7 tests, 7 assertions)"

  sync_to_host
  assert_dir_not_empty "test_reports"
  assert_file_exists "test_reports/phpunit/unit_scripts.xml"
  assert_file_exists "test_reports/phpunit/unit_modules.xml"
  assert_file_exists "test_reports/phpunit/unit_themes.xml"

  substep "Run grouped Unit tests"
  export DREVOPS_TEST_UNIT_GROUP=unit:subtraction
  run ahoy test-unit
  assert_success
  assert_output_contains "Running Unit tests."
  # Scripts.
  assert_output_contains "OK (3 tests, 3 assertions)"
  # Modules.
  assert_output_contains "OK (4 tests, 4 assertions)"
  unset DREVOPS_TEST_UNIT_GROUP

  substep "Assert that Drupal Unit test failure bypassing works"
  # Prepare failing test.
  sed -i -e "s/assertEquals/assertNotEquals/g" "${webroot}/modules/custom/sw_core/tests/src/Unit/ExampleTest.php"
  sync_to_container

  # Assert without bypass.
  run ahoy test-unit
  assert_failure
  assert_output_contains "Running Unit tests."
  assert_output_contains "Unit tests failed."
  sync_to_host

  # Assert failure bypass.
  add_var_to_file .env "DREVOPS_TEST_UNIT_ALLOW_FAILURE" "1" && ahoy up cli && sync_to_container
  run ahoy test-unit
  assert_success
  assert_output_contains "Running Unit tests."
  assert_output_contains "Unit tests failed, but failure is allowed."
  sync_to_host

  restore_file .env && ahoy up cli
}

assert_ahoy_test_kernel() {
  local webroot="${1:-web}"

  step "Run Drupal Kernel tests"

  substep "Run all Kernel tests"
  run ahoy test-kernel
  assert_success
  assert_output_contains "Running Kernel tests."
  assert_output_contains "OK (5 tests, 5 assertions)"
  assert_output_contains "Kernel tests passed."
  sync_to_host
  assert_dir_not_empty "test_reports"
  assert_file_exists "test_reports/phpunit/kernel_modules.xml"

  substep "Run grouped Kernel tests"
  export DREVOPS_TEST_KERNEL_GROUP=kernel:subtraction
  run ahoy test-kernel
  assert_success
  assert_output_contains "Running Kernel tests."
  assert_output_contains "OK (3 tests, 3 assertions)"
  assert_output_contains "Kernel tests passed"
  unset DREVOPS_TEST_KERNEL_GROUP

  substep "Assert that Kernel test failure bypassing works"
  # Prepare failing test.
  sed -i -e "s/assertEquals/assertNotEquals/g" "${webroot}/modules/custom/sw_core/tests/src/Kernel/ExampleTest.php"
  sync_to_container

  # Assert without bypass.
  run ahoy test-kernel
  assert_failure
  assert_output_contains "Running Kernel tests."
  assert_output_contains "Kernel tests failed."
  sync_to_host

  # Assert failure bypass.
  add_var_to_file .env "DREVOPS_TEST_KERNEL_ALLOW_FAILURE" "1" && ahoy up cli && sync_to_container
  run ahoy test-kernel
  assert_success
  assert_output_contains "Running Kernel tests."
  assert_output_contains "Kernel tests failed, but failure is allowed."
  sync_to_host

  restore_file .env && ahoy up cli
}

assert_ahoy_test_functional() {
  local webroot="${1:-web}"

  step "Run Drupal Functional tests"

  substep "Run all Functional tests"
  run ahoy test-functional
  assert_success
  assert_output_contains "Running Functional tests."
  assert_output_contains "OK (2 tests, 4 assertions)"
  assert_output_contains "Functional tests passed."
  sync_to_host
  assert_dir_not_empty "test_reports"
  assert_file_exists "test_reports/phpunit/functional_modules.xml"

  substep "Run grouped Functional tests"
  export DREVOPS_TEST_FUNCTIONAL_GROUP=functional:subtraction
  run ahoy test-functional
  assert_success
  assert_output_contains "Running Functional tests."
  assert_output_contains "OK (1 test, 2 assertions)"
  assert_output_contains "Functional tests passed"
  unset DREVOPS_TEST_FUNCTIONAL_GROUP

  substep "Assert that Functional test failure bypassing works"
  # Prepare failing test.
  sed -i -e "s/assertEquals/assertNotEquals/g" "${webroot}/modules/custom/sw_core/tests/src/Functional/ExampleTest.php"
  sync_to_container

  # Assert without bypass.
  run ahoy test-functional
  assert_failure
  assert_output_contains "Running Functional tests."
  assert_output_contains "Functional tests failed."
  sync_to_host

  # Assert failure bypass.
  add_var_to_file .env "DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE" "1" && ahoy up cli && sync_to_container
  run ahoy test-functional
  assert_success
  assert_output_contains "Running Functional tests."
  assert_output_contains "Functional tests failed, but failure is allowed."
  sync_to_host

  restore_file .env && ahoy up cli
}

assert_ahoy_test_bdd_fast() {
  step "Run BDD tests - fast"

  substep "Run all BDD tests"
  run ahoy test-bdd
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  sync_to_host
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
}

assert_ahoy_test_bdd() {
  step "Run BDD tests"

  substep "Run all BDD tests"
  run ahoy test-bdd
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  sync_to_host
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*

  substep "Run tagged BDD tests"
  assert_dir_empty tests/behat/screenshots
  run ahoy test-bdd -- --tags=smoke
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  assert_file_exists "tests/behat/screenshots/*html"
  assert_file_exists "tests/behat/screenshots/*png"
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Run tagged BDD tests based on DREVOPS_TEST_BEHAT_TAGS variable"
  assert_dir_empty tests/behat/screenshots
  export DREVOPS_TEST_BEHAT_TAGS=smoke
  run ahoy test-bdd
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  unset DREVOPS_TEST_BEHAT_TAGS
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  assert_file_exists "tests/behat/screenshots/*html"
  assert_file_exists "tests/behat/screenshots/*png"
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Run profile BDD tests based on DREVOPS_TEST_BEHAT_PROFILE variable"
  assert_dir_empty tests/behat/screenshots
  ahoy cli mkdir -p /app/test_reports/behat
  export DREVOPS_TEST_BEHAT_PROFILE=p0
  run ahoy test-bdd
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  unset DREVOPS_TEST_BEHAT_PROFILE
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  assert_file_exists "tests/behat/screenshots/*html"
  assert_file_exists "tests/behat/screenshots/*png"
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Assert that Behat tests failure works"
  echo 'And I should be in the "some-non-existing-page" path' >>tests/behat/features/homepage.feature
  sync_to_container
  assert_dir_empty tests/behat/screenshots
  run ahoy test-bdd
  assert_failure
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests failed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Assert that Behat tests failure bypassing works"
  add_var_to_file .env "DREVOPS_TEST_BDD_ALLOW_FAILURE" "1" && ahoy up cli && sync_to_container
  run ahoy test-bdd
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests failed, but failure is allowed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*
  # Remove failing step from the feature.
  trim_file tests/behat/features/homepage.feature
  sync_to_container
  restore_file .env && ahoy up cli && sync_to_container

  substep "Run a single Behat test"
  assert_dir_empty tests/behat/screenshots
  run ahoy test-bdd tests/behat/features/homepage.feature
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests passed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Assert that a single Behat test failure works"
  assert_dir_empty tests/behat/screenshots
  echo 'And I should be in the "some-non-existing-page" path' >>tests/behat/features/homepage.feature
  run ahoy up cli && sync_to_container
  run ahoy test-bdd tests/behat/features/homepage.feature
  assert_failure
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests failed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*

  substep "Assert that a single Behat test failure bypassing works"
  assert_dir_empty tests/behat/screenshots
  add_var_to_file .env "DREVOPS_TEST_BDD_ALLOW_FAILURE" "1" && ahoy up cli && sync_to_container
  run ahoy test-bdd tests/behat/features/homepage.feature
  assert_success
  assert_output_contains "Running BDD tests."
  assert_output_contains "BDD tests failed, but failure is allowed."
  sync_to_host
  assert_dir_not_empty test_reports
  assert_file_exists test_reports/behat/default.xml
  rm -rf test_reports/*
  ahoy cli rm -rf /app/test_reports/*
  assert_dir_not_empty tests/behat/screenshots
  rm -rf tests/behat/screenshots/*
  ahoy cli rm -rf /app/tests/behat/screenshots/*
  # Remove failing step from the feature.
  trim_file tests/behat/features/homepage.feature
  # Remove fail bypass.
  restore_file .env && ahoy up cli && sync_to_container
}

assert_ahoy_fei() {
  local webroot="${1:-web}"

  step "FE dependencies install"

  substep "Remove existing Node modules"
  rm -Rf "${webroot}/themes/custom/star_wars/node_modules" || true
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/node_modules"
  sync_to_container

  substep "Install Node modules"
  run ahoy fei
  assert_success
  sync_to_host
  assert_dir_exists "${webroot}/themes/custom/star_wars/node_modules"
}

assert_ahoy_fe() {
  local webroot="${1:-web}"

  step "FE assets"

  substep "Build FE assets for production"
  assert_file_not_contains "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css" "#7e57e2"
  echo '$color-tester: #7e57e2;' >>"${webroot}/themes/custom/star_wars/scss/_variables.scss"
  echo '$color-primary: $color-tester;' >>"${webroot}/themes/custom/star_wars/scss/_variables.scss"
  sync_to_container
  ahoy fe
  sync_to_host
  assert_file_contains "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css" "background:#7e57e2"

  substep "Build FE assets for development"
  assert_file_not_contains "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css" "#91ea5e"
  echo '$color-please: #91ea5e;' >>"${webroot}/themes/custom/star_wars/scss/_variables.scss"
  echo '$color-primary: $color-please;' >>"${webroot}/themes/custom/star_wars/scss/_variables.scss"
  sync_to_container
  ahoy fed
  sync_to_host
  # Note that assets compiled for development are not minified (contains spaces
  # between properties and their values).
  assert_file_contains "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css" "background: #91ea5e"
}

assert_ahoy_debug() {
  step "Xdebug"

  substep "Enable debug"
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
  # Using "reat" from "Create" or "Creating".
  assert_output_contains "reat"
  assert_output_contains "Enabled debug"
  # Assert that Xdebug is enabled from the inside of the container.
  run ahoy cli "php -v | grep Xdebug"
  assert_success
  # Assert info correctly shown from the outside of the container.
  run ahoy info
  assert_success
  assert_output_not_contains "Disabled"
  assert_output_contains "Enabled"
  # Assert that command when debugging is enabled does not restart the stack.
  run ahoy debug
  assert_success
  assert_output_not_contains "reat"
  assert_output_contains "Enabled debug"

  substep "Disable debug"
  # Assert that restarting the stack does not have Xdebug enabled.
  run ahoy up
  assert_success
  # Assert that the stack has restarted.
  assert_output_contains "reat"
  # Assert that Xdebug is disabled from the inside of the container.
  run ahoy cli "php -v | grep Xdebug"
  assert_failure
  # Assert info correctly shown from the outside of the container.
  run ahoy info
  assert_success
  assert_output_contains "Xdebug"
  assert_output_contains "Disabled"
  assert_output_not_contains "Enabled"
}

assert_solr() {
  step "Solr"

  run ahoy cli curl -s "http://solr:8983/solr/drupal/select?q=*:*&rows=0&wt=json" | jq '.response.numFound'
  assert_output_contains 2
}

assert_redis() {
  step "Redis"

  substep "Redis service is running"
  run docker compose exec redis redis-cli FLUSHALL
  assert_output_contains "OK"

  substep "Redis integration is disabled"
  ahoy drush cr
  ahoy cli curl -L -s "http://nginx:8080" >/dev/null
  run docker compose exec redis redis-cli --scan
  assert_output_not_contains "config"

  substep "Restart with environment variable"
  add_var_to_file .env "DREVOPS_REDIS_ENABLED" "1"
  sync_to_container
  DREVOPS_REDIS_ENABLED=1 ahoy up cli
  sleep 10
  ahoy drush cr
  ahoy cli curl -L -s "http://nginx:8080" >/dev/null
  run docker compose exec redis redis-cli --scan
  assert_output_contains "config"
}

assert_ahoy_clean() {
  local webroot="${1:-web}"

  step "Clean"

  # Prepare to assert that manually created file is not removed.
  touch untracked_file.txt

  create_development_settings "${webroot}"
  mkdir -p "tests/behat/screenshots"
  assert_dir_exists "tests/behat/screenshots"

  ahoy clean
  # Assert that initial DrevOps files have not been removed.
  assert_files_present_common "" "" "" "" "" "${webroot}"
  assert_files_present_deployment
  assert_files_present_no_integration_acquia "" "" "${webroot}"
  assert_files_present_no_integration_lagoon "" "" "${webroot}"
  assert_files_present_no_integration_ftp

  assert_dir_not_exists "${webroot}/modules/contrib"
  assert_dir_not_exists "${webroot}/themes/contrib"
  assert_dir_not_exists "vendor"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/node_modules"
  assert_dir_exists "tests/behat/screenshots"

  # Assert manually created local settings file exists.
  assert_file_exists "${webroot}/sites/default/settings.local.php"
  # Assert manually created local services file exists.
  assert_file_exists "${webroot}/sites/default/services.local.yml"
  # Assert manually created file still exists.
  assert_file_exists "untracked_file.txt"
  # Assert IDE config file still exists.
  assert_file_exists ".idea/idea_file.txt"

  assert_git_repo

  remove_development_settings "${webroot}"
}

assert_ahoy_reset() {
  local webroot="${1:-web}"

  step "Reset"

  create_development_settings "${webroot}"

  mkdir -p "tests/behat/screenshots"
  assert_dir_exists "tests/behat/screenshots"

  ahoy reset

  assert_files_present_common "" "" "" "" "" "${webroot}"
  assert_files_present_deployment
  assert_files_present_no_integration_acquia "" "" "${webroot}"
  assert_files_present_no_integration_lagoon "" "" "${webroot}"
  assert_files_present_no_integration_ftp

  assert_file_not_exists "${webroot}/sites/default/settings.local.php"
  assert_file_not_exists "${webroot}/sites/default/services.local.yml"

  # Assert manually created file still exists.
  assert_file_not_exists "untracked_file.txt"
  # Assert IDE config file still exists.
  assert_file_exists ".idea/idea_file.txt"

  assert_dir_not_exists "tests/behat/screenshots"

  assert_git_repo

  remove_development_settings "${webroot}"
}
