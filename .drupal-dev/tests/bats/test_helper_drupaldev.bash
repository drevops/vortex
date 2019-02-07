#!/usr/bin/env bash
#
# Helpers related to Drupal-Dev common testing functionality.
#

################################################################################
#                          HOOK IMPLEMENTATIONS                                #
################################################################################

# To run installation tests, several fixture directories are required. They are
# defined and created in setup() test method.
#
# $BUILD_DIR - root build directory where the rest of fixture directories located.
#
# $CURRENT_PROJECT_DIR - directory where install script is executed. May have
# existing project files (e.g. from previous installations) or be empty (to
# facilitate brand-new install).
#
# $DST_PROJECT_DIR - directory where Drupal-Dev may be installed to. By default,
# install uses $CURRENT_PROJECT_DIR as a destination, but we use
# $DST_PROJECT_DIR to test a scenario where different destination is provided.
#
# $LOCAL_REPO_DIR - directory where install script will be sourcing the instance
# of Drupal-Dev.
#
# $APP_TMP_DIR - directory where the application may store it's temporary files.
setup(){
  DRUPAL_VERSION=${DRUPAL_VERSION:-8}
  CUR_DIR="$(pwd)"
  BUILD_DIR="${BUILD_DIR:-"${BATS_TMPDIR}/drupal-dev-bats"}"

  CURRENT_PROJECT_DIR="${BUILD_DIR}/star_wars"
  DST_PROJECT_DIR="${BUILD_DIR}/dst"
  LOCAL_REPO_DIR="${BUILD_DIR}/local_repo"
  APP_TMP_DIR="${BUILD_DIR}/tmp"

  prepare_fixture_dir "${BUILD_DIR}"
  prepare_fixture_dir "${CURRENT_PROJECT_DIR}"
  prepare_fixture_dir "${DST_PROJECT_DIR}"
  prepare_fixture_dir "${LOCAL_REPO_DIR}"
  prepare_fixture_dir "${APP_TMP_DIR}"
  pushd "${BUILD_DIR}" > /dev/null || exit 1

  LOCAL_REPO_COMMIT="$(prepare_local_repo "${LOCAL_REPO_DIR}")"
}

teardown(){
  popd > /dev/null || cd "${CUR_DIR}" || exit 1
}

################################################################################
#                               ASSERTIONS                                     #
################################################################################

assert_added_files(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  assert_added_files_no_integrations "${dir}" "${suffix}"

  # Assert Acquia integration preserved.
  assert_added_files_integration_acquia "${dir}" "${suffix}"

  # Assert Lagoon integration preserved.
  assert_added_files_integration_lagoon "${dir}" "${suffix}"
}

assert_added_files_no_integrations(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null

  # All Drupal-Dev own files removed.
  assert_dir_not_exists .drupal-dev
  # Stub profile removed.
  assert_dir_not_exists docroot/profiles/custom/mysite_profile
  # Stub code module removed.
  assert_dir_not_exists docroot/modules/custom/mysite_core
  # Stub theme removed.
  assert_dir_not_exists docroot/themes/custom/mysitetheme

  # Site profile created.
  assert_dir_exists docroot/profiles/custom/${suffix}_profile
  assert_file_exists docroot/profiles/custom/${suffix}_profile/${suffix}_profile.info.yml
  # Site core module created.
  assert_dir_exists docroot/modules/custom/${suffix}_core
  assert_file_exists docroot/modules/custom/${suffix}_core/${suffix}_core.info.yml
  assert_file_exists docroot/modules/custom/${suffix}_core/${suffix}_core.install
  assert_file_exists docroot/modules/custom/${suffix}_core/${suffix}_core.module
  assert_file_exists docroot/modules/custom/${suffix}_core/${suffix}_core.constants.php

  # Site theme created.
  assert_dir_exists docroot/themes/custom/${suffix}
  assert_file_exists docroot/themes/custom/${suffix}/js/${suffix}.js
  assert_dir_exists docroot/themes/custom/${suffix}/scss
  assert_file_exists docroot/themes/custom/${suffix}/.gitignore
  assert_file_exists docroot/themes/custom/${suffix}/${suffix}.info.yml
  assert_file_exists docroot/themes/custom/${suffix}/${suffix}.libraries.yml
  assert_file_exists docroot/themes/custom/${suffix}/${suffix}.theme

  # Settings files exist.
  # @note The permissions can be 644 or 664 depending on the umask of OS. Also,
  # git only track 644 or 755.
  assert_file_exists docroot/sites/default/settings.php
  assert_file_mode docroot/sites/default/settings.php "644"

  assert_file_exists docroot/sites/default/default.settings.local.php
  assert_file_mode docroot/sites/default/default.settings.local.php "644"

  assert_file_exists docroot/sites/default/default.services.local.yml
  assert_file_mode docroot/sites/default/default.services.local.yml "644"

  # Documentation information added.
  assert_file_exists FAQs.md

  # Init command removed from Ahoy config.
  assert_file_exists .ahoy.yml

  # Assert all stub strings were replaced.
  assert_dir_not_contains_string "mysite"
  assert_dir_not_contains_string "MYSITE"
  assert_dir_not_contains_string "mysitetheme"
  assert_dir_not_contains_string "myorg"
  assert_dir_not_contains_string "mysiteurl"
  # Assert all special comments were removed.
  assert_dir_not_contains_string "#|"
  assert_dir_not_contains_string "#<"
  assert_dir_not_contains_string "#>"

  # Assert that project name is correct.
  assert_file_contains .env "PROJECT=\"${suffix}\""

  # Assert that required files were not locally excluded.
  if [ -d ".git" ] ; then
    assert_file_not_contains .git/info/exclude ".circleci/config.yml"
    assert_file_not_contains .git/info/exclude "docroot/sites/default/settings.php"
    assert_file_not_contains .git/info/exclude "docroot/sites/default/services.yml"
  fi

  popd > /dev/null
}

assert_added_files_integration_acquia(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null

  # Acquia integration preserved.
  assert_dir_exists hooks
  assert_dir_exists hooks/library
  assert_file_mode hooks/library/clear-cache.sh "755"
  assert_file_mode hooks/library/enable-shield.sh "755"
  assert_file_mode hooks/library/flush-varnish.sh "755"
  assert_file_mode hooks/library/import-config.sh "755"
  assert_file_mode hooks/library/update-db.sh "755"
  assert_file_exists scripts/download-backup-acquia.sh
  assert_file_exists DEPLOYMENT.md
  assert_file_contains README.md "Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)"
  assert_file_contains docroot/sites/default/settings.php "if (file_exists('/var/www/site-php')) {"
  assert_file_contains .env "AC_API_DB_SITE="
  assert_file_contains .env "AC_API_DB_ENV="
  assert_file_contains .env "AC_API_DB_NAME="
  assert_file_contains .ahoy.yml "AC_API_DB_SITE="
  assert_file_contains .ahoy.yml "AC_API_DB_ENV="
  assert_file_contains .ahoy.yml "AC_API_DB_NAME="

  popd > /dev/null
}

assert_added_files_no_integration_acquia(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null

  # Acquia integration preserved.
  assert_dir_not_exists hooks
  assert_dir_not_exists hooks/library
  assert_file_not_exists scripts/download-backup-acquia.sh
  assert_file_not_contains docroot/sites/default/settings.php "if (file_exists('/var/www/site-php')) {"
  assert_file_not_contains .env "AC_API_DB_SITE="
  assert_file_not_contains .env "AC_API_DB_ENV="
  assert_file_not_contains .env "AC_API_DB_NAME="
  assert_file_not_contains .ahoy.yml "AC_API_DB_SITE="
  assert_file_not_contains .ahoy.yml "AC_API_DB_ENV="
  assert_file_not_contains .ahoy.yml "AC_API_DB_NAME="

  popd > /dev/null
}

assert_added_files_integration_lagoon(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null

  assert_file_exists .lagoon.yml
  assert_file_exists drush/aliases.drushrc.php
  assert_file_contains docker-compose.yml "labels"
  assert_file_contains docker-compose.yml "lagoon.type: cli-persistent"
  assert_file_contains docker-compose.yml "lagoon.persistent.name: nginx"
  assert_file_contains docker-compose.yml "lagoon.persistent: /app/docroot/sites/default/files/"
  assert_file_contains docker-compose.yml "lagoon.type: nginx-php-persistent"
  assert_file_contains docker-compose.yml "lagoon.name: nginx"
  assert_file_contains docker-compose.yml "lagoon.type: mariadb"
  assert_file_contains docker-compose.yml "lagoon.type: solr"
  assert_file_contains docker-compose.yml "lagoon.type: none"

  popd > /dev/null
}

assert_added_files_no_integration_lagoon(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null

  assert_file_not_exists .lagoon.yml
  assert_file_not_exists drush/aliases.drushrc.php
  assert_file_not_contains docker-compose.yml "labels"
  assert_file_not_contains docker-compose.yml "lagoon.type: cli-persistent"
  assert_file_not_contains docker-compose.yml "lagoon.persistent.name: nginx"
  assert_file_not_contains docker-compose.yml "lagoon.persistent: /app/docroot/sites/default/files/"
  assert_file_not_contains docker-compose.yml "lagoon.type: nginx-php-persistent"
  assert_file_not_contains docker-compose.yml "lagoon.name: nginx"
  assert_file_not_contains docker-compose.yml "lagoon.type: mariadb"
  assert_file_not_contains docker-compose.yml "lagoon.type: solr"
  assert_file_not_contains docker-compose.yml "lagoon.type: none"

  popd > /dev/null
}

################################################################################
#                               UTILITIES                                      #
################################################################################

# Run install script.
run_install(){
  pushd "${CURRENT_PROJECT_DIR}" > /dev/null

  # Force install script to be downloaded from the local repo for testing.
  export DRUPALDEV_LOCAL_REPO="${LOCAL_REPO_DIR}"
  export DRUPALDEV_DEBUG=1
  # @todo:dev Remove below once tests are passing.
  export DRUPALDEV_TMP_DIR="${APP_TMP_DIR}"
  "${CUR_DIR}"/install.sh "$@" >&3

  popd > /dev/null
}

# Copy source code at the latest commit to the destination directory.
copy_code(){
  local dst="${1:-${BUILD_DIR}}"
  pushd "${CUR_DIR}" > /dev/null || exit 1
  # Copy latest commit to the build directory.
  git archive --format=tar HEAD | (cd "${dst}" && tar -xf -)
  popd > /dev/null || exit 1
}

# Prepare local repository from the current codebase.
prepare_local_repo(){
  local dir="${1}"
  local do_copy_code="${2:-1}"
  local commit

  if [ ${do_copy_code} -eq 1 ]; then
    prepare_fixture_dir "${dir}"
    copy_code "${dir}"
  fi

  pushd "${dir}" > /dev/null || exit 1

  commit=$(git_add_all_files)

  popd > /dev/null || cd "${CUR_DIR}" || exit 1

  echo ${commit}
}

git_add(){
  local dir="${1}"
  local file="${2}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add "${dir}/${file}" > /dev/null
}

git_commit(){
  local dir="${1}"
  local message="${2}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" > /dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_add_all(){
  local dir="${1}"
  local message="${2}"
  git add -A
  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" > /dev/null
  commit=$(git rev-parse HEAD)
  echo "${commit}"
}

git_init(){
  local dir="${1}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init > /dev/null
  git config user.name "someone"
  git config user.email "someone@someplace.com"
}
