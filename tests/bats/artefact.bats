#!/usr/bin/env bats
#
# Test runner for artefact tests.
#

load test_helper
load test_helper_drupaldev

@test "Artefact" {
  # Source directory for initialised codebase.
  # If not provided - directory will be created and a site will be initialised.
  # This is to facilitate local testing.
  SRC_DIR="${SRC_DIR:-}"

  # "Remote" repository to deploy the artefact to. It is located in the host
  # filesystem and just treated as a remote for currently installed codebase.
  REMOTE_REPO_DIR=${REMOTE_REPO_DIR:-${BUILD_DIR}/artefact_remote}

  step "Starting ARTEFACT tests"

  if [ ! "${SRC_DIR}" ]; then
    SRC_DIR="${BUILD_DIR}/artefact_src"
    step "Artefact source directory is not provided - using directory ${SRC_DIR}"
    prepare_fixture_dir "${SRC_DIR}"

    # We need to use "current" directory as a place where the deployment script
    # is going to run from, while "SRC_DIR" is a place where files are taken
    # from for deployment. They may be the same place, but we are testing them
    # if they are separate, because most likely SRC_DIR will contain code
    # built on previous build stages.
    pushd "${CURRENT_PROJECT_DIR}" > /dev/null

    assert_no_added_files_no_integrations "${CURRENT_PROJECT_DIR}"
    export DRUPALDEV_REMOVE_DEMO=0
    run_install
    assert_added_files "${CURRENT_PROJECT_DIR}"
    assert_git_repo "${CURRENT_PROJECT_DIR}"

    step "Building site"

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

    step "Download the database"
    assert_file_not_exists .data/db.sql
    ahoy download-db
    assert_file_exists .data/db.sql

    step "Build project"
    docker network prune -f > /dev/null && docker network inspect amazeeio-network > /dev/null || docker network create amazeeio-network
    ahoy up -- --build --force-recreate >&3
    sync_to_host
    assert_added_files "${CURRENT_PROJECT_DIR}"

    popd > /dev/null

    step "Copying built codebase into code source directory ${SRC_DIR}"
    cp -R "${CURRENT_PROJECT_DIR}/." "${SRC_DIR}/"
  else
    step "Using provided SRC_DIR ${SRC_DIR}"
    assert_dir_not_empty "${SRC_DIR}"
  fi

  assert_added_files "${SRC_DIR}"

  step "Preparing remote repo directory ${REMOTE_REPO_DIR}"
  prepare_fixture_dir "${REMOTE_REPO_DIR}"
  git_init "${REMOTE_REPO_DIR}" 1
  DEPLOY_REMOTE="${REMOTE_REPO_DIR}"/.git

  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  step "Installing dependencies, including artefact builder"
  ahoy install-dev
  sync_to_host

  step "Push artefact to remote repository"
  vendor/bin/robo --ansi --load-from vendor/integratedexperts/robo-git-artefact/RoboFile.php artefact "${DEPLOY_REMOTE}" --root="${CURRENT_PROJECT_DIR}" --src="${SRC_DIR}" --gitignore="${SRC_DIR}"/.gitignore.artefact --push --no-cleanup -vvvv >&3

  step "Checkout currently pushed branch on remote"
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch | sed 's/\*\s//g' | xargs git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" checkout

  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_REPO_DIR}" branch >&3

  step "Assert remote artefact files"
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/.circleci
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/.data
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/.docker
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/.github
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/.gitignore.artefact
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/node_modules
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/patches
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/screenshots
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/scripts
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/tests
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.ahoy.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.dockerignore
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.editorconfig
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.env
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.eslintrc.json
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.lagoon.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/.sass-lint.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/behat.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/composer.json
  assert_file_not_exists "${REMOTE_REPO_DIR}"/composer.lock
  assert_file_not_exists "${REMOTE_REPO_DIR}"/dependencies.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docker-compose.yml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/Gruntfile.js
  assert_file_not_exists "${REMOTE_REPO_DIR}"/LICENSE
  assert_file_not_exists "${REMOTE_REPO_DIR}"/package.json
  assert_file_not_exists "${REMOTE_REPO_DIR}"/package-lock.json
  assert_file_not_exists "${REMOTE_REPO_DIR}"/phpcs.xml
  assert_file_not_exists "${REMOTE_REPO_DIR}"/README.md

  assert_dir_exists "${REMOTE_REPO_DIR}"/vendor
  # Site profile present.
  assert_dir_exists "${REMOTE_REPO_DIR}"/docroot/profiles/custom/star_wars_profile
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/profiles/custom/star_wars_profile/star_wars_profile.info.yml
  # Site core module present.
  assert_dir_exists "${REMOTE_REPO_DIR}"/docroot/modules/custom/star_wars_core
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/modules/custom/star_wars_core/star_wars_core.info.yml
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/modules/custom/star_wars_core/star_wars_core.install
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/modules/custom/star_wars_core/star_wars_core.module
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/modules/custom/star_wars_core/star_wars_core.constants.php
  # Site theme present.
  assert_dir_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/.gitignore
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/star_wars.info.yml
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/star_wars.libraries.yml
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/star_wars.theme

  # Settings files present.
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/sites/default/settings.php
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/sites/default/settings.generated.php:
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/sites/default/default.local.settings.php:
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/sites/default/local.settings.php:
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/sites/default/default.settings.php:

  # Only minified compiled CSS present.
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/build/css/star_wars.min.css
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/build/css/star_wars.css
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/scss
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/css

  # Only minified compiled JS exists.
  assert_file_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/build/js/star_wars.min.js
  assert_file_contains "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/build/js/star_wars.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.star_wars"
  assert_file_not_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/build/js/star_wars.js
  assert_dir_not_exists "${REMOTE_REPO_DIR}"/docroot/themes/custom/star_wars/js

  # Acquia hooks are present.
  assert_dir_exists "${REMOTE_REPO_DIR}"/hooks/library
  assert_dir_exists "${REMOTE_REPO_DIR}"/hooks/prod
  assert_dir_exists "${REMOTE_REPO_DIR}"/hooks/dev
  assert_dir_exists "${REMOTE_REPO_DIR}"/hooks/dev/post-code-update
  assert_symlink_exists "${REMOTE_REPO_DIR}"/hooks/dev/post-code-deploy
  assert_symlink_exists "${REMOTE_REPO_DIR}"/hooks/dev/post-db-copy
  assert_symlink_exists "${REMOTE_REPO_DIR}"/hooks/test

  popd > /dev/null
}
