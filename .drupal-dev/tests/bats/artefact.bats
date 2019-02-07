#!/usr/bin/env bats
#
# Test runner for artefact tests.
#

load test_helper
load test_helper_drupaldev

@test "Artefact" {
  SRC_DIR=${SRC_DIR:-}
  REMOTE_DIR=${REMOTE_DIR:-${BUILD_DIR}/artefact-remote}
  DRUPAL_VERSION=${DRUPAL_VERSION:-7}

  [ ! "${SRC_DIR}" ] && flunk "Source directory is a required argument"

  # Prepare remote repo directory.
  prepare_fixture_dir "${REMOTE_DIR}"
  DEPLOY_REMOTE="${REMOTE_DIR}"/.git
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_DIR}" init

  copy_code

  # Install dev dependencies.
  composer install -n --ansi --prefer-dist --ignore-platform-reqs
  cp -a "${CUR_DIR}"/.git "${SRC_DIR}/"
  cp -a "${CUR_DIR}"/.gitignore.artefact "${SRC_DIR}"

  # Push artefact to remote repository.
  vendor/bin/robo --ansi --load-from vendor/integratedexperts/robo-git-artefact/RoboFile.php artefact "${DEPLOY_REMOTE}" --root="${BUILD_DIR}" --src="${SRC_DIR}" --gitignore="${SRC_DIR}"/.gitignore.artefact --push

  # Checkout currently pushed branch on remote.
  git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_DIR}" branch | xargs git --git-dir="${DEPLOY_REMOTE}" --work-tree="${REMOTE_DIR}" checkout

  # Other files and directories are not present.
  assert_dir_not_exists "${REMOTE_DIR}"/.circleci
  assert_dir_not_exists "${REMOTE_DIR}"/.data
  assert_dir_not_exists "${REMOTE_DIR}"/.drupal-dev
  assert_dir_not_exists "${REMOTE_DIR}"/.docker
  assert_dir_not_exists "${REMOTE_DIR}"/.github
  assert_dir_not_exists "${REMOTE_DIR}"/.gitignore.artefact
  assert_dir_not_exists "${REMOTE_DIR}"/node_modules
  assert_dir_not_exists "${REMOTE_DIR}"/patches
  assert_dir_not_exists "${REMOTE_DIR}"/screenshots
  assert_dir_not_exists "${REMOTE_DIR}"/scripts
  assert_dir_not_exists "${REMOTE_DIR}"/tests
  assert_file_not_exists "${REMOTE_DIR}"/.ahoy.yml
  assert_file_not_exists "${REMOTE_DIR}"/.dockerignore
  assert_file_not_exists "${REMOTE_DIR}"/.editorconfig
  assert_file_not_exists "${REMOTE_DIR}"/.env
  assert_file_not_exists "${REMOTE_DIR}"/.eslintrc.json
  assert_file_not_exists "${REMOTE_DIR}"/.lagoon.yml
  assert_file_not_exists "${REMOTE_DIR}"/.sass-lint.yml
  assert_file_not_exists "${REMOTE_DIR}"/behat.yml
  assert_file_not_exists "${REMOTE_DIR}"/composer.json
  assert_file_not_exists "${REMOTE_DIR}"/composer.lock
  assert_file_not_exists "${REMOTE_DIR}"/dependencies.yml
  assert_file_not_exists "${REMOTE_DIR}"/docker-compose.yml
  assert_file_not_exists "${REMOTE_DIR}"/Gruntfile.js
  assert_file_not_exists "${REMOTE_DIR}"/LICENSE
  assert_file_not_exists "${REMOTE_DIR}"/package.json
  assert_file_not_exists "${REMOTE_DIR}"/package-lock.json
  assert_file_not_exists "${REMOTE_DIR}"/phpcs.xml
  assert_file_not_exists "${REMOTE_DIR}"/README.md
  assert_dir_exists "${REMOTE_DIR}"/vendor
  # Site profile present.
  assert_dir_exists "${REMOTE_DIR}"/docroot/profiles/custom/mysite_profile
  assert_file_exists "${REMOTE_DIR}"/docroot/profiles/custom/mysite_profile/mysite_profile.info.yml
  # Site core module present.
  assert_dir_exists "${REMOTE_DIR}"/docroot/modules/custom/mysite_core
  assert_file_exists "${REMOTE_DIR}"/docroot/modules/custom/mysite_core/mysite_core.info.yml
  assert_file_exists "${REMOTE_DIR}"/docroot/modules/custom/mysite_core/mysite_core.install
  assert_file_exists "${REMOTE_DIR}"/docroot/modules/custom/mysite_core/mysite_core.module
  assert_file_exists "${REMOTE_DIR}"/docroot/modules/custom/mysite_core/mysite_core.constants.php
  # Site theme present.
  assert_dir_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/.gitignore
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/mysitetheme.info.yml
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/mysitetheme.libraries.yml
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/mysitetheme.theme

  # Settings files present.
  assert_file_exists "${REMOTE_DIR}"/docroot/sites/default/settings.php
  assert_file_not_exists "${REMOTE_DIR}"/docroot/sites/default/settings.generated.php:
  assert_file_not_exists "${REMOTE_DIR}"/docroot/sites/default/default.local.settings.php:
  assert_file_not_exists "${REMOTE_DIR}"/docroot/sites/default/local.settings.php:
  assert_file_not_exists "${REMOTE_DIR}"/docroot/sites/default/default.settings.php:

  # Only minified compiled CSS present.
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/css/mysitetheme.min.css
  assert_file_contains "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/css/mysitetheme.min.css "background:#fff"
  assert_file_not_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/css/mysitetheme.css
  assert_dir_not_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/scss
  assert_dir_not_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/css

  # Only minified compiled JS exists.
  assert_file_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/js/mysitetheme.min.js
  assert_file_contains "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/js/mysitetheme.min.js "function(t,Drupal){\"use strict\";Drupal.behaviors.mysite"
  assert_file_not_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/build/js/mysitetheme.js
  assert_dir_not_exists "${REMOTE_DIR}"/docroot/themes/custom/mysitetheme/js

  # Acquia hooks are present.
  assert_dir_exists "${REMOTE_DIR}"/hooks/library
  assert_dir_exists "${REMOTE_DIR}"/hooks/prod
  assert_dir_exists "${REMOTE_DIR}"/hooks/dev
  assert_dir_exists "${REMOTE_DIR}"/hooks/dev/post-code-update
  assert_symlink_exists "${REMOTE_DIR}"/hooks/dev/post-code-deploy
  assert_symlink_exists "${REMOTE_DIR}"/hooks/dev/post-db-copy
  assert_symlink_exists "${REMOTE_DIR}"/hooks/test
}
