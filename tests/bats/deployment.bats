#!/usr/bin/env bats
#
# Test runner for deployment tests.
#

load test_helper
load test_helper_drupaldev

@test "Deployment; no integration" {
  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artefact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/deployment_remote}

  step "Starting DEPLOYMENT tests"

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    step "Deployment source directory is not provided - using directory ${SRC_DIR}"
    prepare_fixture_dir "${SRC_DIR}"

    # Disable Acquia integration for this test to run independent deployment.
    export DRUPALDEV_OPT_PRESERVE_ACQUIA=0

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages.
    provision_site "${CURRENT_PROJECT_DIR}"

    assert_files_present_common "${CURRENT_PROJECT_DIR}"
    assert_files_present_deployment  "${CURRENT_PROJECT_DIR}"
    assert_files_present_no_integration_acquia  "${CURRENT_PROJECT_DIR}"
    assert_files_present_integration_lagoon  "${CURRENT_PROJECT_DIR}"
    assert_files_present_no_integration_ftp  "${CURRENT_PROJECT_DIR}"

    step "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    step "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_no_integration_acquia "${SRC_DIR}"
  assert_files_present_integration_lagoon "${SRC_DIR}"
  assert_files_present_no_integration_ftp "${SRC_DIR}"
  assert_git_repo "${SRC_DIR}"

  # Make sure that one of the excluded directories will be ignored in the
  # deployment artifact.
  mkdir -p "${SRC_DIR}"/node_modules
  touch "${SRC_DIR}"/node_modules/test.txt

  step "Preparing remote repo directory ${REMOTE_REPO_DIR}"
  prepare_fixture_dir "${REMOTE_REPO_DIR}"
  git_init "${REMOTE_REPO_DIR}" 1

  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  step "Running deployment"
  export DEPLOY_REMOTE="${REMOTE_REPO_DIR}"/.git
  export DEPLOY_ROOT="${CURRENT_PROJECT_DIR}"
  export DEPLOY_SRC="${SRC_DIR}"
  source scripts/deploy.sh >&3

  step "Checkout currently pushed branch on remote"
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch | sed 's/\*\s//g' | xargs git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" checkout
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch >&3

  step "Assert remote deployment files"
  assert_deployment_files_present "${REMOTE_REPO_DIR}"

  # Assert Acquia hooks are absent.
  assert_files_present_no_integration_acquia "${REMOTE_REPO_DIR}"

  popd > /dev/null
}

@test "Deployment; Acquia integration" {
  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artefact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/deployment_remote}

  step "Starting DEPLOYMENT tests"

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/deployment_src"
    step "Deployment source directory is not provided - using directory ${SRC_DIR}"
    prepare_fixture_dir "${SRC_DIR}"

    # Enable Acquia integration for this test to run independent deployment.
    export DRUPALDEV_OPT_PRESERVE_ACQUIA=Y

    step "Create .env.local file with Acquia credentials"
    {
      echo AC_API_USER_NAME="dummy";
      echo AC_API_USER_PASS="dummy";
    } >> "${CURRENT_PROJECT_DIR}"/.env.local

    # Override download from Acquia with a special flag. This still allows to
    # validate that download script expects credentials, but does not actually
    # run the download (it would fail since there is no Acquia environment
    # attached to this test).
    # A DEMO_DB_TEST database will be used as actual database to provision site.
    echo "DB_DOWNLOAD_PROCEED=0" >> "${CURRENT_PROJECT_DIR}"/.env.local

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages.
    provision_site "${CURRENT_PROJECT_DIR}"

    assert_files_present_common "${CURRENT_PROJECT_DIR}"
    assert_files_present_deployment  "${CURRENT_PROJECT_DIR}"
    assert_files_present_integration_acquia  "${CURRENT_PROJECT_DIR}"
    assert_files_present_integration_lagoon  "${CURRENT_PROJECT_DIR}"
    assert_files_present_no_integration_ftp  "${CURRENT_PROJECT_DIR}"

    step "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    step "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  # Make sure that all files were copied out from the container or passed from
  # the previous stage of the build.
  assert_files_present_common "${SRC_DIR}"
  assert_files_present_deployment "${SRC_DIR}"
  assert_files_present_integration_acquia "${SRC_DIR}"
  assert_files_present_integration_lagoon "${SRC_DIR}"
  assert_files_present_no_integration_ftp "${SRC_DIR}"
  assert_git_repo "${SRC_DIR}"

  # Make sure that one of the excluded directories will be ignored in the
  # deployment artifact.
  mkdir -p "${SRC_DIR}"/node_modules
  touch "${SRC_DIR}"/node_modules/test.txt

  step "Preparing remote repo directory ${REMOTE_REPO_DIR}"
  prepare_fixture_dir "${REMOTE_REPO_DIR}"
  git_init "${REMOTE_REPO_DIR}" 1

  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  step "Running deployment"
  export DEPLOY_REMOTE="${REMOTE_REPO_DIR}"/.git
  export DEPLOY_ROOT="${CURRENT_PROJECT_DIR}"
  export DEPLOY_SRC="${SRC_DIR}"
  source scripts/deploy.sh >&3

  step "Checkout currently pushed branch on remote"
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch | sed 's/\*\s//g' | xargs git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" checkout
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch >&3

  step "Assert remote deployment files"
  assert_deployment_files_present "${REMOTE_REPO_DIR}"

  # Assert Acquia hooks are present.
  assert_files_present_integration_acquia "${REMOTE_REPO_DIR}" "star_wars" 0

  popd > /dev/null
}

assert_deployment_files_present(){
  local dir="${1}"

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
  # Site profile present.
  assert_dir_exists "${dir}"/docroot/profiles/custom/star_wars_profile
  assert_file_exists "${dir}"/docroot/profiles/custom/star_wars_profile/star_wars_profile.info.yml
  # Site core module present.
  assert_dir_exists "${dir}"/docroot/modules/custom/star_wars_core
  assert_file_exists "${dir}"/docroot/modules/custom/star_wars_core/star_wars_core.info.yml
  assert_file_exists "${dir}"/docroot/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists "${dir}"/docroot/modules/custom/star_wars_core/star_wars_core.module
  assert_file_exists "${dir}"/docroot/modules/custom/star_wars_core/star_wars_core.constants.php
  # Site theme present.
  assert_dir_exists "${dir}"/docroot/themes/custom/star_wars
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/.gitignore
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/star_wars.info.yml
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/star_wars.libraries.yml
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/star_wars.theme

  # Settings files present.
  assert_file_exists "${dir}"/docroot/sites/default/settings.php
  assert_file_not_exists "${dir}"/docroot/sites/default/settings.generated.php:
  assert_file_not_exists "${dir}"/docroot/sites/default/default.local.settings.php:
  assert_file_not_exists "${dir}"/docroot/sites/default/local.settings.php:
  assert_file_not_exists "${dir}"/docroot/sites/default/default.settings.php:

  # Only minified compiled CSS present.
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_exists "${dir}"/docroot/themes/custom/star_wars/build/css/star_wars.css
  assert_dir_not_exists "${dir}"/docroot/themes/custom/star_wars/scss
  assert_dir_not_exists "${dir}"/docroot/themes/custom/star_wars/css

  # Only minified compiled JS exists.
  assert_file_exists "${dir}"/docroot/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains "${dir}"/docroot/themes/custom/star_wars/build/js/star_wars.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.star_wars"
  assert_file_not_exists "${dir}"/docroot/themes/custom/star_wars/build/js/star_wars.js
  assert_dir_not_exists "${dir}"/docroot/themes/custom/star_wars/js

  # Assert configuration dir exists.
  assert_dir_exists "${dir}"/config/default
}

provision_site(){
  local dir="${1}"

  pushd "${dir}" > /dev/null

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
  git_add_all "${dir}" "Init Drupal-Dev config"

  # In this test, the database is downloaded from public gist specified in
  # DEMO_DB_TEST variable.
  step "Download the database"
  assert_file_not_exists .data/db.sql
  ahoy download-db
  assert_file_exists .data/db.sql

  step "Build project"
  docker network prune -f > /dev/null && docker network inspect amazeeio-network > /dev/null || docker network create amazeeio-network
  ahoy down
  ahoy up -- --build --force-recreate >&3
  sync_to_host

  popd > /dev/null
}
