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
# The "build" in this context is a place to store assets produce by the install
# script during the test.
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
  DRUPAL_VERSION="${DRUPAL_VERSION:-7}"
  CUR_DIR="$(pwd)"
  BUILD_DIR="${BUILD_DIR:-"${BATS_TEST_TMPDIR}/drupal-dev-$(random_string)"}"

  CURRENT_PROJECT_DIR="${BUILD_DIR}/star_wars"
  DST_PROJECT_DIR="${BUILD_DIR}/dst"
  LOCAL_REPO_DIR="${BUILD_DIR}/local_repo"
  APP_TMP_DIR="${BUILD_DIR}/tmp"

  # Disable checks used on host machine.
  export DOCTOR_CHECK_TOOLS=0
  export DOCTOR_CHECK_PYGMY=0
  export DOCTOR_CHECK_PORT=0
  export DOCTOR_CHECK_SSH=0
  export DOCTOR_CHECK_WEBSERVER=0
  export DOCTOR_CHECK_BOOTSTRAP=0

  export DRUPAL_VERSION
  export CUR_DIR
  export BUILD_DIR
  export CURRENT_PROJECT_DIR
  export DST_PROJECT_DIR
  export LOCAL_REPO_DIR
  export APP_TMP_DIR

  prepare_fixture_dir "${BUILD_DIR}"
  prepare_fixture_dir "${CURRENT_PROJECT_DIR}"
  prepare_fixture_dir "${DST_PROJECT_DIR}"
  prepare_fixture_dir "${LOCAL_REPO_DIR}"
  prepare_fixture_dir "${APP_TMP_DIR}"
  pushd "${BUILD_DIR}" > /dev/null || exit 1

  prepare_local_repo "${LOCAL_REPO_DIR}" >/dev/null
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

  # Assert Drupal profile not present by default.
  assert_files_present_no_profile "${dir}" "${suffix}"

  # Assert Drupal is not freshly installed by default.
  assert_files_present_no_fresh_install "${dir}" "${suffix}"

  # Assert deployments preserved.
  assert_files_present_deployment "${dir}" "${suffix}"

  # Assert Acquia integration preserved.
  assert_files_present_integration_acquia "${dir}" "${suffix}"

  # Assert Lagoon integration preserved.
  assert_files_present_integration_lagoon "${dir}" "${suffix}"

  # Assert FTP integration removed by default.
  assert_files_present_no_integration_ftp "${dir}" "${suffix}"

  # Assert Dependabot integration preserved.
  assert_files_present_integration_dependabot "${dir}" "${suffix}"
}

assert_files_present_common(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  # Stub profile removed.
  assert_dir_not_exists "docroot/profiles/your_site_profile"
  # Stub code module removed.
  assert_dir_not_exists "docroot/sites/all/modules/custom/your_site_core"
  # Stub theme removed.
  assert_dir_not_exists "docroot/sites/all/themes/custom/your_site_theme"

  # Site core module created.
  assert_dir_exists "docroot/sites/all/modules/custom/${suffix}_core"
  assert_file_exists "docroot/sites/all/modules/custom/${suffix}_core/${suffix}_core.info"
  assert_file_exists "docroot/sites/all/modules/custom/${suffix}_core/${suffix}_core.install"
  assert_file_exists "docroot/sites/all/modules/custom/${suffix}_core/${suffix}_core.module"

  # Site theme created.
  assert_dir_exists "docroot/sites/all/themes/custom/${suffix}"
  assert_file_exists "docroot/sites/all/themes/custom/${suffix}/js/${suffix}.js"
  assert_dir_exists "docroot/sites/all/themes/custom/${suffix}/scss"
  assert_dir_exists "docroot/sites/all/themes/custom/${suffix}/images"
  assert_dir_exists "docroot/sites/all/themes/custom/${suffix}/fonts"
  assert_file_exists "docroot/sites/all/themes/custom/${suffix}/.gitignore"
  assert_file_exists "docroot/sites/all/themes/custom/${suffix}/${suffix}.info"
  assert_file_exists "docroot/sites/all/themes/custom/${suffix}/template.php"

  # Comparing binary files.
  assert_files_equal "${LOCAL_REPO_DIR}/docroot/sites/all/themes/custom/your_site_theme/screenshot.png" "docroot/sites/all/themes/custom/${suffix}/screenshot.png"

  # Settings files exist.
  # @note The permissions can be 644 or 664 depending on the umask of OS. Also,
  # git only track 644 or 755.
  assert_file_exists "docroot/sites/default/settings.php"
  assert_file_mode "docroot/sites/default/settings.php" "644"

  assert_file_exists "docroot/sites/default/default.settings.local.php"
  assert_file_mode "docroot/sites/default/default.settings.local.php" "644"

  # Documentation information added.
  assert_file_exists "FAQs.md"

  # Init command removed from Ahoy config.
  assert_file_exists ".ahoy.yml"

  # Special case to fix all occurrences of the stub in core files to exclude
  # false-positives from the assertions below.
  replace_core_stubs "${dir}" "your_site"

  # Assert all stub strings were replaced.
  assert_dir_not_contains_string "${dir}" "your_site"
  assert_dir_not_contains_string "${dir}" "YOURSITE"
  assert_dir_not_contains_string "${dir}" "your_site_theme"
  assert_dir_not_contains_string "${dir}" "your_org"
  assert_dir_not_contains_string "${dir}" "YOURORG"
  assert_dir_not_contains_string "${dir}" "your-site-url"
  # Assert all special comments were removed.
  assert_dir_not_contains_string "${dir}" "#;"
  assert_dir_not_contains_string "${dir}" "#;<"
  assert_dir_not_contains_string "${dir}" "#;>"

  # Assert that project name is correct.
  assert_file_contains .env "PROJECT=\"${suffix}\""
  assert_file_contains .env "LOCALDEV_URL=\"${suffix/_/-}.docker.amazee.io\""

  # Assert that documentation was processed correctly.
  assert_file_not_contains README.md "# Drupal-Dev"

  # Assert that Drupal-Dev files removed.
  assert_file_not_exists "install.sh"
  assert_file_not_exists "LICENSE"
  assert_file_not_exists ".circleci/drupal_dev-test.sh"
  assert_file_not_exists ".circleci/drupal_dev-test-deployment.sh"
  assert_dir_not_exists "tests/bats"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_test"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_test_deployment"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_deploy"
  assert_file_not_contains ".circleci/config.yml" "drupal_dev_deploy_tags"

  # Assert that Drupal-Dev version was replaced.
  assert_file_contains "README.md" "badge/Drupal--Dev-${DRUPAL_VERSION}.x-blue.svg"
  assert_file_contains "README.md" "https://github.com/integratedexperts/drupal-dev/tree/${DRUPAL_VERSION}.x"

  # Assert that required files were not locally excluded.
  if [ -d ".git" ] ; then
    assert_file_not_contains .git/info/exclude "README.md"
    assert_file_not_contains .git/info/exclude ".circleci/config.yml"
    assert_file_not_contains .git/info/exclude "docroot/sites/default/settings.php"
  fi

  popd > /dev/null || exit 1
}

assert_files_not_present_common(){
  local dir="${1}"
  local suffix="${2:-star_wars}"
  local has_required_files="${3:-0}"

  pushd "${dir}" > /dev/null || exit 1

  assert_dir_not_exists "docroot/sites/all/modules/custom/your_site_core"
  assert_dir_not_exists "docroot/sites/all/themes/custom/your_site_theme"
  assert_dir_not_exists "docroot/profiles/${suffix}_profile"
  assert_dir_not_exists "docroot/sites/all/modules/custom/${suffix}_core"
  assert_dir_not_exists "docroot/sites/all/themes/custom/${suffix}"
  assert_file_not_exists "docroot/sites/default/default.settings.local.php"

  assert_file_not_exists "FAQs.md"
  assert_file_not_exists ".ahoy.yml"

  if [ "${has_required_files}" -eq 1 ] ; then
    assert_file_exists "README.md"
    assert_file_exists ".circleci/config.yml"
    assert_file_exists "docroot/sites/default/settings.php"
  else
    assert_file_not_exists "README.md"
    assert_file_not_exists ".circleci/config.yml"
    assert_file_not_exists "docroot/sites/default/settings.php"
  fi

  popd > /dev/null || exit 1
}

assert_files_present_profile(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  # Site profile created.
  assert_dir_exists "docroot/profiles/${suffix}profile"
  assert_file_exists "docroot/profiles/${suffix}profile/${suffix}profile.info"
  assert_file_contains ".env" "DRUPAL_PROFILE="
  assert_file_contains ".env" "docroot/profiles/${suffix}profile,"

  popd > /dev/null || exit 1
}

assert_files_present_no_profile(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  # Site profile created.
  assert_dir_not_exists "docroot/profiles/${suffix}profile"
  assert_file_contains ".env" "DRUPAL_PROFILE=\"standard\""
  assert_file_not_contains ".env" "docroot/profiles/${suffix}profile,"
  # Assert that there is no renaming of the custom profile with core profile name.
  assert_dir_not_exists "docroot/profiles/standard"
  assert_file_not_contains ".env" "docroot/profiles/standard,"

  popd > /dev/null || exit 1
}

assert_files_present_fresh_install(){
 local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists ".ahoy.yml"
  assert_file_not_contains ".ahoy.yml" "ahoy title \"Installing site from existing database dump\""
  assert_file_contains ".ahoy.yml" "ahoy title \"Installing a fresh site from \${DRUPAL_PROFILE} profile\""
  assert_file_not_contains ".ahoy.yml" "download-db:"

  popd > /dev/null || exit 1
}

assert_files_present_no_fresh_install(){
 local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists ".ahoy.yml"
  assert_file_contains ".ahoy.yml" "ahoy title \"Installing site from existing database dump\""
  assert_file_not_contains ".ahoy.yml" "ahoy title \"Installing a fresh site from \${DRUPAL_PROFILE} profile\""
  assert_file_contains ".ahoy.yml" "download-db:"

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

  # 'Required' files can be asserted for modifications only if they were not
  # committed.
  if [ "${has_committed_files}" -eq 0 ]; then
    assert_file_not_contains "README.md" "Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)"
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
  assert_file_mode "hooks/library/update-db.sh" "755"

  assert_dir_exists "hooks/dev"
  assert_dir_exists "hooks/dev/post-code-update"
  assert_symlink_exists "hooks/dev/post-code-update/1.clear-cache.sh"
  assert_symlink_exists "hooks/dev/post-code-update/2.update-db.sh"
  assert_symlink_exists "hooks/dev/post-code-update/3.enable-shield.sh"
  assert_symlink_exists "hooks/dev/post-code-update/4.flush-varnish.sh"
  assert_symlink_exists "hooks/dev/post-code-deploy"
  assert_symlink_exists "hooks/dev/post-db-copy"

  assert_symlink_exists "hooks/test"

  assert_dir_exists "hooks/prod"
  assert_dir_exists "hooks/prod/post-code-deploy"
  assert_symlink_exists "hooks/prod/post-code-update"
  assert_symlink_not_exists "hooks/prod/post-db-copy"
  assert_symlink_exists "hooks/prod/post-code-deploy/1.clear-cache.sh"
  assert_symlink_exists "hooks/prod/post-code-deploy/2.update-db.sh"
  assert_symlink_exists "hooks/prod/post-code-deploy/3.enable-shield.sh"

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

assert_files_present_integration_dependabot(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_exists ".dependabot/config.yml"
  assert_file_not_contains ".dependabot/config.yml" "target_branch"
  assert_file_contains README.md "Automated patching"

  popd > /dev/null || exit 1
}

assert_files_present_no_integration_dependabot(){
  local dir="${1}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" > /dev/null || exit 1

  assert_file_not_exists ".dependabot/config.yml"
  assert_dir_not_contains_string "${dir}" "Dependabot"

  popd > /dev/null || exit 1
}

fixture_readme(){
  local dir="${1:-.}"
  local name="${2:-Star Wars}"
  local org="${3:-Star Wars Org}"

  cat <<EOT >> "${dir}"/README.md
# ${name}
Drupal 8 implementation of ${name} for ${org}

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY DRUPAL-DEV TO TRACK INTEGRATION)

[![drupal-dev.io](https://img.shields.io/badge/Drupal--Dev-DRUPALDEV_VERSION_URLENCODED-blue.svg)](https://github.com/integratedexperts/drupal-dev/tree/DRUPALDEV_VERSION)

some other text
EOT
}

fixture_composerjson(){
  local dir="${1:-.}"
  local name="${2}"
  local machine_name="${3}"
  local org="${4}"
  local org_machine_name="${5}"

  cat <<EOT >> "${dir}"/composer.json
{
    "name": "${org_machine_name}/${machine_name}",
    "description": "Drupal 8 implementation of ${name} for ${org}"
}
EOT
}
################################################################################
#                               UTILITIES                                      #
################################################################################

# Run install script.
run_install(){
  pushd "${CURRENT_PROJECT_DIR}" > /dev/null || exit 1

  # Force install script to be downloaded from the local repo for testing.
  export DRUPALDEV_LOCAL_REPO="${LOCAL_REPO_DIR}"
  # Use unique temporary directory for each run.
  DRUPALDEV_TMP_DIR="${APP_TMP_DIR}/$(random_string)"
  prepare_fixture_dir "${DRUPALDEV_TMP_DIR}"
  export DRUPALDEV_TMP_DIR
  # Show debug information (for easy debug of tests).
  export DRUPALDEV_DEBUG=1
  run "${CUR_DIR}"/install.sh "$@"
  # Special treatment for cases where volumes are not mounted from the host.
  fix_host_dependencies "$@"
  popd > /dev/null || exit 1

  # shellcheck disable=SC2154
  echo "${output}"
}

# Run install in interactive mode.
#
# 'nothing' stands for user not providing an input and accepting suggested
# default values.
#
# @code
# answers=(
#   "Star wars" # name
#   "nothing" # machine_name
#   "nothing" # org
#   "nothing" # morh_machine_name
#   "nothing" # module_prefix
#   "nothing" # profile
#   "nothing" # theme
#   "nothing" # URL
#   "nothing" # fresh_install
#   "nothing" # preserve_deployment
#   "nothing" # preserve_acquia
#   "nothing" # preserve_lagoon
#   "nothing" # preserve_ftp
#   "nothing" # preserve_dependabot
#   "nothing" # remove_drupaldev_info
# )
# output=$(run_install_interactive "${answers[@]}")
# @endcode
run_install_interactive(){
  local answers=("${@}")
  local input

  for i in "${answers[@]}";
  do
    val="${i}"
    [ "${i}" == "nothing" ] && val='\n' || val="${val}"'\n'
    input="${input}""${val}"
  done

  # shellcheck disable=SC2059
  printf "$input" | run_install "--interactive"
}

#
# Create a stub of installed dependencies.
#
# Used for fast unit testing of install functionality.
#
install_dependencies_stub(){
  local dir="${1:-.}"

  mktouch "${dir}/docroot/index.php"
  mktouch "${dir}/docroot/README.txt"
  echo "example of your_site added to core file" > "${dir}/docroot/README.txt"
  mktouch "${dir}/docroot/sites/all/modules/contrib/somemodule/somemodule.info"
  mktouch "${dir}/docroot/sites/all/themes/contrib/sometheme/sometheme.info"
  mktouch "${dir}/docroot/profiles/zzzsomeprofile/zzzsomeprofile.info"
  mktouch "${dir}/docroot/sites/default/somesettingsfile.php"
  mktouch "${dir}/docroot/sites/default/files/somepublicfile.php"
  mktouch "${dir}/vendor/somevendor/somepackage/somepackage.php"
  mktouch "${dir}/vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "${dir}/vendor/somevendor/somepackage/composer.json"
  mktouch "${dir}/node_modules/somevendor/somepackage/somepackage.js"

  mktouch "${dir}/docroot/sites/all/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch "${dir}/screenshots/s1.jpg"
  mktouch "${dir}/.data/db.sql"

  mktouch "${dir}/docroot/sites/default/settings.local.php"
  mktouch "${dir}/.env.local"
  echo "version: \"2.3\"" > "${dir}/docker-compose.override.yml"
}

replace_core_stubs(){
  local dir="${1}"
  local token="${2}"

  replace_string_content  "${token}" "some_other_site" "${dir}/docroot"
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
  commit=$(git_add_all_commit "${dir}" "Initial commit")

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

git_add_all_commit(){
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
    git --work-tree="${dir}" --git-dir="${dir}/.git" config receive.denyCurrentBranch updateInstead > /dev/null
  fi
}

# Replace string content in the directory.
replace_string_content() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')

  set +e
  grep -rI \
  --exclude-dir=".git" \
  --exclude-dir=".idea" \
  --exclude-dir="vendor" \
  --exclude-dir="node_modules" \
  --exclude-dir=".data" \
  -l "${needle}" "${dir}" \
  | xargs sed "${sed_opts[@]}" "s@$needle@$replacement@g" || true
  set -e
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

# Special treatment for cases where volumes are not mounted from the host.
fix_host_dependencies(){
  # Replicate behaviour of install.sh script to extract destination directory
  # passed as an argument.
  # shellcheck disable=SC2235
  ([ "${1}" == "--interactive" ] || [ "${1}" == "-i" ]) && shift
  # Destination directory, that can be overridden with the first argument to this script.
  DST_DIR="${DST_DIR:-$(pwd)}"
  DST_DIR=${1:-${DST_DIR}}

  pushd "${DST_DIR}" > /dev/null || exit 1

  if [ "${VOLUMES_MOUNTED:-1}" != "1" ] ; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  popd > /dev/null || exit 1
}
