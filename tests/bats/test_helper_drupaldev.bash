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
  DRUPAL_VERSION="${DRUPAL_VERSION:-8}"
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

  prepare_local_repo "${LOCAL_REPO_DIR}"
}

teardown(){
  popd > /dev/null || cd "${CUR_DIR}" || exit 1
}

################################################################################
#                               ASSERTIONS                                     #
################################################################################

assert_files_present(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  assert_files_present_common "${dir}" "${suffix}"

  # Assert deployments preserved.
  assert_files_present_deployment "${dir}" "${suffix}"

  # Assert Acquia integration preserved.
  assert_files_present_integration_acquia "${dir}" "${suffix}"

  # Assert Lagoon integration preserved.
  assert_files_present_integration_lagoon "${dir}" "${suffix}"

  # Assert FTP integration removed by default.
  assert_files_present_no_integration_ftp "${dir}" "${suffix}"

  # Assert dependencies.io integration preserved.
  assert_files_present_integration_dependenciesio "${dir}" "${suffix}"
}

assert_files_present_common(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  # Stub profile removed.
  assert_dir_not_exists "docroot/profiles/custom/yoursite_profile"
  # Stub code module removed.
  assert_dir_not_exists "docroot/modules/custom/yoursite_core"
  # Stub theme removed.
  assert_dir_not_exists "docroot/themes/custom/yoursitetheme"

  # Site profile created.
  assert_dir_exists "docroot/profiles/custom/${suffix}_profile"
  assert_file_exists "docroot/profiles/custom/${suffix}_profile/${suffix}_profile.info.yml"
  # Site core module created.
  assert_dir_exists "docroot/modules/custom/${suffix}_core"
  assert_file_exists "docroot/modules/custom/${suffix}_core/${suffix}_core.info.yml"
  assert_file_exists "docroot/modules/custom/${suffix}_core/${suffix}_core.install"
  assert_file_exists "docroot/modules/custom/${suffix}_core/${suffix}_core.module"
  assert_file_exists "docroot/modules/custom/${suffix}_core/${suffix}_core.constants.php"

  # Site theme created.
  assert_dir_exists "docroot/themes/custom/${suffix}"
  assert_file_exists "docroot/themes/custom/${suffix}/js/${suffix}.js"
  assert_dir_exists "docroot/themes/custom/${suffix}/scss"
  assert_file_exists "docroot/themes/custom/${suffix}/.gitignore"
  assert_file_exists "docroot/themes/custom/${suffix}/${suffix}.info.yml"
  assert_file_exists "docroot/themes/custom/${suffix}/${suffix}.libraries.yml"
  assert_file_exists "docroot/themes/custom/${suffix}/${suffix}.theme"

  # Comparing binary files.
  assert_files_equal "${LOCAL_REPO_DIR}/docroot/themes/custom/yoursitetheme/screenshot.png" "docroot/themes/custom/${suffix}/screenshot.png"

  # Settings files exist.
  # @note The permissions can be 644 or 664 depending on the umask of OS. Also,
  # git only track 644 or 755.
  assert_file_exists "docroot/sites/default/settings.php"
  assert_file_mode "docroot/sites/default/settings.php" "644"

  assert_file_exists "docroot/sites/default/default.settings.local.php"
  assert_file_mode "docroot/sites/default/default.settings.local.php" "644"

  assert_file_exists "docroot/sites/default/default.services.local.yml"
  assert_file_mode "docroot/sites/default/default.services.local.yml" "644"

  # Documentation information added.
  assert_file_exists "FAQs.md"

  # Init command removed from Ahoy config.
  assert_file_exists ".ahoy.yml"

  # Assert all stub strings were replaced.
  assert_dir_not_contains_string "${dir}" "yoursite"
  assert_dir_not_contains_string "${dir}" "YOURSITE"
  assert_dir_not_contains_string "${dir}" "yoursitetheme"
  assert_dir_not_contains_string "${dir}" "yourorg"
  assert_dir_not_contains_string "${dir}" "YOURORG"
  assert_dir_not_contains_string "${dir}" "yoursiteurl"
  # Assert all special comments were removed.
  assert_dir_not_contains_string "${dir}" "#;"
  assert_dir_not_contains_string "${dir}" "#;<"
  assert_dir_not_contains_string "${dir}" "#;>"

  # Assert that project name is correct.
  assert_file_contains .env "PROJECT=\"${suffix}\""

  # Assert that documentation was processed correctly.
  assert_file_not_contains README.md "# Drupal-Dev"

  # Assert that Drupal-Dev files removed.
  assert_file_not_exists "install.sh"
  assert_file_not_exists "LICENSE"
  assert_dir_not_exists "tests/bats"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_test"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_test_deployment"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_deploy"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_deploy_tags"

  # Assert that required files were not locally excluded.
  if [ -d ".git" ] ; then
    assert_file_not_contains .git/info/exclude "drupal-dev.sh"
    assert_file_not_contains .git/info/exclude "README.md"
    assert_file_not_contains .git/info/exclude ".circleci/config.yml"
    assert_file_not_contains .git/info/exclude "docroot/sites/default/settings.php"
    assert_file_not_contains .git/info/exclude "docroot/sites/default/services.yml"
  fi

  popd > /dev/null || exit 1
}

assert_files_not_present_common(){
  local dir="${1}"
  local suffix="${2:-star_wars}"
  local has_committed_files="${3:-0}"

  pushd "${dir}" > /dev/null || exit 1

  assert_dir_not_exists "docroot/profiles/custom/yoursite_profile"
  assert_dir_not_exists "docroot/modules/custom/yoursite_core"
  assert_dir_not_exists "docroot/themes/custom/yoursitetheme"
  assert_dir_not_exists "docroot/profiles/custom/${suffix}_profile"
  assert_dir_not_exists "docroot/modules/custom/${suffix}_core"
  assert_dir_not_exists "docroot/themes/custom/${suffix}"
  assert_file_not_exists "docroot/sites/default/default.settings.local.php"
  assert_file_not_exists "docroot/sites/default/default.services.local.yml"
  assert_file_not_exists "FAQs.md"
  assert_file_not_exists ".ahoy.yml"

  if [ "${has_committed_files}" -eq 1 ] ; then
    assert_file_exists "README.md"
    assert_file_exists "drupal-dev.sh"
    assert_file_exists ".circleci/config.yml"
    assert_file_exists "docroot/sites/default/settings.php"
    assert_file_exists "docroot/sites/default/services.yml"
  else
    assert_file_not_exists "README.md"
    assert_file_not_exists "drupal-dev.sh"
    assert_file_not_exists ".circleci/config.yml"
    assert_file_not_exists "docroot/sites/default/settings.php"
    assert_file_not_exists "docroot/sites/default/services.yml"
  fi

  popd > /dev/null || exit 1
}

assert_files_present_deployment(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists ".gitignore.deployment"
  assert_file_exists "DEPLOYMENT.md"
  assert_file_exists ".circleci/deploy.sh"
  assert_file_contains ".circleci/config.yml" "deploy: &job_deploy"
  assert_file_contains ".circleci/config.yml" "deploy_tags: &job_deploy_tags"
  assert_file_contains "README.md" "Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)"

  popd > /dev/null || exit 1
}

assert_files_present_no_deployment(){
  local dir="${1}"
  local suffix="${2:-star_wars}"
  local has_committed_files="${3:-0}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_not_exists ".gitignore.deployment"
  assert_file_not_exists "DEPLOYMENT.md"
  assert_file_not_exists ".circleci/deploy.sh"
  assert_file_not_contains "README.md" "Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)"
  if [ "${has_committed_files}" -eq 0 ]; then
    assert_file_not_contains ".circleci/config.yml" "deploy: &job_deploy"
    assert_file_not_contains ".circleci/config.yml" "deploy_tags: &job_deploy_tags"
  fi

  popd > /dev/null || exit 1
}

assert_files_present_integration_acquia(){
  local dir="${1}"
  local suffix="${2:-star_wars}"
  local include_scripts="${3:-1}"

  pushd "${dir}" > /dev/null || exit 1

  assert_dir_exists "hooks"
  assert_dir_exists "hooks/library"
  assert_file_mode "hooks/library/clear-cache.sh" "755"
  assert_file_mode "hooks/library/enable-shield.sh" "755"
  assert_file_mode "hooks/library/flush-varnish.sh" "755"
  assert_file_mode "hooks/library/import-config.sh" "755"
  assert_file_mode "hooks/library/update-db.sh" "755"

  assert_dir_exists "hooks/dev"
  assert_dir_exists "hooks/dev/post-code-update"
  assert_symlink_exists "hooks/dev/post-code-update/1.clear-cache.sh"
  assert_symlink_exists "hooks/dev/post-code-update/2.update-db.sh"
  assert_symlink_exists "hooks/dev/post-code-update/3.import-config.sh"
  assert_symlink_exists "hooks/dev/post-code-update/4.enable-shield.sh"
  assert_symlink_exists "hooks/dev/post-code-update/5.flush-varnish.sh"
  assert_symlink_exists "hooks/dev/post-code-deploy"
  assert_symlink_exists "hooks/dev/post-db-copy"

  assert_symlink_exists "hooks/test"

  assert_dir_exists "hooks/prod"
  assert_dir_exists "hooks/prod/post-code-deploy"
  assert_symlink_exists "hooks/prod/post-code-update"
  assert_symlink_not_exists "hooks/prod/post-db-copy"
  assert_symlink_exists "hooks/prod/post-code-deploy/1.clear-cache.sh"
  assert_symlink_exists "hooks/prod/post-code-deploy/2.update-db.sh"
  assert_symlink_exists "hooks/prod/post-code-deploy/3.import-config.sh"
  assert_symlink_exists "hooks/prod/post-code-deploy/4.enable-shield.sh"

  assert_file_contains "docroot/sites/default/settings.php" "if (file_exists('/var/www/site-php')) {"

  if [ "${include_scripts}" -eq 1 ]; then
    assert_file_exists "scripts/download-backup-acquia.sh"
    assert_file_contains ".env" "AC_API_DB_SITE="
    assert_file_contains ".env" "AC_API_DB_ENV="
    assert_file_contains ".env" "AC_API_DB_NAME="
    assert_file_contains ".ahoy.yml" "AC_API_DB_SITE="
    assert_file_contains ".ahoy.yml" "AC_API_DB_ENV="
    assert_file_contains ".ahoy.yml" "AC_API_DB_NAME="
  fi

  popd > /dev/null || exit 1
}

assert_files_present_no_integration_acquia(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_dir_not_exists "hooks"
  assert_dir_not_exists "hooks/library"
  assert_file_not_exists "scripts/download-backup-acquia.sh"
  assert_file_not_contains "docroot/sites/default/settings.php" "if (file_exists('/var/www/site-php')) {"
  assert_file_not_contains ".env" "AC_API_DB_SITE="
  assert_file_not_contains ".env" "AC_API_DB_ENV="
  assert_file_not_contains ".env" "AC_API_DB_NAME="
  assert_file_not_contains ".ahoy.yml" "AC_API_DB_SITE="
  assert_file_not_contains ".ahoy.yml" "AC_API_DB_ENV="
  assert_file_not_contains ".ahoy.yml" "AC_API_DB_NAME="
  assert_dir_not_contains_string "${dir}" "AC_API_USER_NAME"
  assert_dir_not_contains_string "${dir}" "AC_API_USER_PASS"

  popd > /dev/null || exit 1
}

assert_files_present_integration_lagoon(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists ".lagoon.yml"
  assert_file_exists "drush/aliases.drushrc.php"
  assert_file_contains "docker-compose.yml" "labels"
  assert_file_contains "docker-compose.yml" "lagoon.type: cli-persistent"
  assert_file_contains "docker-compose.yml" "lagoon.persistent.name: nginx"
  assert_file_contains "docker-compose.yml" "lagoon.persistent: /app/docroot/sites/default/files/"
  assert_file_contains "docker-compose.yml" "lagoon.type: nginx-php-persistent"
  assert_file_contains "docker-compose.yml" "lagoon.name: nginx"
  assert_file_contains "docker-compose.yml" "lagoon.type: mariadb"
  assert_file_contains "docker-compose.yml" "lagoon.type: solr"
  assert_file_contains "docker-compose.yml" "lagoon.type: none"

  popd > /dev/null || exit 1
}

assert_files_present_no_integration_lagoon(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_not_exists ".lagoon.yml"
  assert_file_not_exists "drush/aliases.drushrc.php"
  assert_file_not_contains "docker-compose.yml" "labels"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: cli-persistent"
  assert_file_not_contains "docker-compose.yml" "lagoon.persistent.name: nginx"
  assert_file_not_contains "docker-compose.yml" "lagoon.persistent: /app/docroot/sites/default/files/"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: nginx-php-persistent"
  assert_file_not_contains "docker-compose.yml" "lagoon.name: nginx"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: mariadb"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: solr"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: none"

  popd > /dev/null || exit 1
}

assert_files_present_integration_ftp(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_contains ".env" "FTP_HOST="
  assert_file_contains ".env" "FTP_PORT="
  assert_file_contains ".env" "FTP_USER="
  assert_file_contains ".env" "FTP_PASS="
  assert_file_contains ".env" "FTP_FILE="

  assert_file_contains ".ahoy.yml" "FTP_HOST"
  assert_file_contains ".ahoy.yml" "FTP_PORT"
  assert_file_contains ".ahoy.yml" "FTP_USER"
  assert_file_contains ".ahoy.yml" "FTP_PASS"
  assert_file_contains ".ahoy.yml" "FTP_FILE"

  popd > /dev/null || exit 1
}

assert_files_present_no_integration_ftp(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_not_contains ".env" "FTP_HOST="
  assert_file_not_contains ".env" "FTP_PORT="
  assert_file_not_contains ".env" "FTP_USER="
  assert_file_not_contains ".env" "FTP_PASS="
  assert_file_not_contains ".env" "FTP_FILE="

  assert_file_not_contains ".ahoy.yml" "FTP_HOST"
  assert_file_not_contains ".ahoy.yml" "FTP_PORT"
  assert_file_not_contains ".ahoy.yml" "FTP_USER"
  assert_file_not_contains ".ahoy.yml" "FTP_PASS"
  assert_file_not_contains ".ahoy.yml" "FTP_FILE"

  popd > /dev/null || exit 1
}

assert_files_present_integration_dependenciesio(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists "dependencies.yml"
  assert_file_contains README.md "Automated patching"

  popd > /dev/null || exit 1
}

assert_files_present_no_integration_dependenciesio(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_not_exists "dependencies.yml"
  assert_dir_not_contains_string "${dir}" "dependencies.io"

  popd > /dev/null || exit 1
}

# Assert that containers are not running.
assert_containers_not_running(){
  # shellcheck disable=SC2046
  [ -f ".env" ] && export $(grep -v '^#' ".env" | xargs) && [ -f ".env.local" ] && export $(grep -v '^#' ".env.local" | xargs)
  # shellcheck disable=SC2143
  if [ -z "$(docker ps -q --no-trunc | grep "$(docker-compose ps -q cli)")" ]; then
    return 0
  else
    return 1
  fi
}

################################################################################
#                               UTILITIES                                      #
################################################################################

# Run install script.
run_install(){
  pushd "${CURRENT_PROJECT_DIR}" > /dev/null || exit 1

  # Force install script to be downloaded from the local repo for testing.
  export DRUPALDEV_LOCAL_REPO="${LOCAL_REPO_DIR}"
  # Use fixture temporary directory.
  export DRUPALDEV_TMP_DIR="${APP_TMP_DIR}"
  # Show debug information (for easy debug of tests).
  export DRUPALDEV_DEBUG=1
  run "${CUR_DIR}"/install.sh "$@"

  popd > /dev/null || exit 1

  # shellcheck disable=SC2154
  echo "${output}"
}

# Copy source code at the latest commit to the destination directory.
copy_code(){
  local dst="${1:-${BUILD_DIR}}"
  assert_dir_exists "${dst}"
  assert_git_repo "${CUR_DIR}"
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

  if [ "${do_copy_code}" -eq 1 ]; then
    prepare_fixture_dir "${dir}"
    copy_code "${dir}"
  fi

  git_init "${dir}"
  [ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name" && git config --global user.name "Some User"
  [ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email" && git config --global user.email "some.user@example.com"
  commit=$(git_add_all "${dir}" "Initial commit")

  echo "${commit}"
}

git_add(){
  local dir="${1}"
  local file="${2}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add "${dir}/${file}" > /dev/null
}

git_add_force(){
  local dir="${1}"
  local file="${2}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add -f "${dir}/${file}" > /dev/null
}

git_commit(){
  local dir="${1}"
  local message="${2}"

  assert_git_repo "${1}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" > /dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_add_all(){
  local dir="${1}"
  local message="${2}"

  assert_git_repo "${1}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" add -A
  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" > /dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_init(){
  local dir="${1}"
  local allow_receive_update="${2:-0}"

  assert_not_git_repo "${1}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init > /dev/null

  if [ "${allow_receive_update}" -eq 1 ]; then
    git --work-tree="${dir}" --git-dir="${dir}/.git"  config receive.denyCurrentBranch updateInstead > /dev/null
  fi
}

# Print step.
step(){
  debug ""
  debug "==> STEP: $1"
}

# Sync files to host in case if volumes are not mounted from host.
sync_to_host(){
  local dst="${1:-.}"
  # shellcheck disable=SC2046
  [ -f ".env" ] && export $(grep -v '^#' ".env" | xargs) && [ -f ".env.local" ] && export $(grep -v '^#' ".env.local" | xargs)
  [ "${VOLUMES_MOUNTED}" == "1" ] && debug "Skipping copying of ${dst} to host" && return
  debug "Syncing from $(docker-compose ps -q cli) to ${dst}"
  docker cp -L "$(docker-compose ps -q cli)":/app/. "${dst}"
}

# Sync files to container in case if volumes are not mounted from host.
sync_to_container(){
  local src="${1:-.}"
  # shellcheck disable=SC2046
  [ -f ".env" ] && export $(grep -v '^#' ".env" | xargs) && [ -f ".env.local" ] && export $(grep -v '^#' ".env.local" | xargs)
  [ "${VOLUMES_MOUNTED}" == "1" ] && debug "Skipping copying of ${src} to container" && return
  debug "Syncing from ${src} to $(docker-compose ps -q cli)"
  docker cp -L "${src}" "$(docker-compose ps -q cli)":/app/
}
