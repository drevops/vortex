#!/usr/bin/env bash
#
# Helpers related to Vortex deployment testing functionality.
#

assert_deployment_files_present() {
  local dir="${1:-$(pwd)}"
  local webroot="web"

  pushd "${dir}" >/dev/null || exit 1

  assert_dir_not_exists .circleci
  assert_dir_not_exists .data
  assert_dir_not_exists .docker
  assert_dir_not_exists .github
  assert_dir_not_exists .gitignore.artifact
  assert_dir_not_exists .logs/screenshots
  assert_dir_not_exists node_modules
  assert_dir_not_exists patches
  assert_dir_not_exists tests
  assert_file_not_exists .ahoy.yml
  assert_file_not_exists .dockerignore
  assert_file_not_exists .editorconfig
  assert_file_not_exists .eslintrc.json
  assert_file_not_exists .lagoon.yml
  assert_file_not_exists .stylelintrc.json
  assert_file_not_exists LICENSE
  assert_file_not_exists README.md
  assert_file_not_exists behat.yml
  assert_file_not_exists composer.lock
  assert_file_not_exists docker-compose.yml
  assert_file_not_exists gherkinlint.json
  assert_file_not_exists phpcs.xml
  assert_file_not_exists phpstan.neon
  assert_file_not_exists renovate.json

  assert_dir_exists scripts
  assert_dir_exists vendor

  # We are passing .env configs to allow to control the project from a single place.
  assert_file_exists .env

  # Site core module present.
  assert_dir_exists "${webroot}/modules/custom/sw_core"
  assert_file_exists "${webroot}/modules/custom/sw_core/sw_core.info.yml"
  assert_file_exists "${webroot}/modules/custom/sw_core/sw_core.module"
  assert_file_exists "${webroot}/modules/custom/sw_core/sw_core.deploy.php"

  # Site theme present.
  assert_dir_exists "${webroot}/themes/custom/star_wars"
  assert_file_exists "${webroot}/themes/custom/star_wars/.gitignore"
  assert_file_exists "${webroot}/themes/custom/star_wars/star_wars.info.yml"
  assert_file_exists "${webroot}/themes/custom/star_wars/star_wars.libraries.yml"
  assert_file_exists "${webroot}/themes/custom/star_wars/star_wars.theme"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/Gruntfile.js"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/package.json"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/package-lock.json"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/.eslintrc.json"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/node_modules"

  # Scaffolding files present.
  assert_file_exists "${webroot}/.editorconfig"
  assert_file_exists "${webroot}/.eslintignore"
  assert_file_exists "${webroot}/.gitattributes"
  assert_file_exists "${webroot}/.htaccess"
  assert_file_exists "${webroot}/autoload.php"
  assert_file_exists "${webroot}/index.php"
  assert_file_exists "${webroot}/robots.txt"
  assert_file_exists "${webroot}/update.php"

  # Settings files present.
  assert_file_exists "${webroot}/sites/default/settings.php"
  assert_file_exists "${webroot}/sites/default/services.yml"
  assert_file_not_exists "${webroot}/sites/default/default.settings.local.php"
  assert_file_not_exists "${webroot}/sites/default/default.services.local.yml"

  # Only minified compiled CSS present.
  assert_file_exists "${webroot}/themes/custom/star_wars/build/css/star_wars.min.css"
  assert_file_not_exists "${webroot}/themes/custom/star_wars/build/css/star_wars.css"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/scss"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/css"

  # Only minified compiled JS exists.
  assert_file_exists "${webroot}/themes/custom/star_wars/build/js/star_wars.min.js"
  assert_file_contains "${webroot}/themes/custom/star_wars/build/js/star_wars.min.js" '!function(Drupal){"use strict";Drupal.behaviors.star_wars'
  assert_file_not_exists "${webroot}/themes/custom/star_wars/build/js/star_wars.js"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/js"

  # Other source asset files do not exist.
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/fonts"
  assert_dir_not_exists "${webroot}/themes/custom/star_wars/images"

  # Assert configuration dir exists.
  assert_dir_exists "config/default"

  # Assert composer.json exists to allow autoloading.
  assert_file_exists composer.json

  popd >/dev/null || exit 1
}

install_and_build_site() {
  local dir="${1:-$(pwd)}"
  local should_build="${2:-1}"
  shift || true
  shift || true
  local answers=("$@")

  pushd "${dir}" >/dev/null || exit 1

  assert_files_not_present_common

  step "Initialise the project with the default settings"

  # shellcheck disable=SC2128
  if [ -n "${answers:-}" ]; then
    run_installer_interactive "${answers[@]}"
  else
    run_installer_quiet
  fi

  assert_files_present_common
  assert_git_repo

  # Special treatment for cases where volumes are not mounted from the host.
  if [ "${VORTEX_DEV_VOLUMES_MOUNTED:-}" != "1" ]; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  step "Add all files to new git repo"
  git_add_all_commit "Init Vortex config" "${dir}"

  if [ "${should_build:-}" = "1" ]; then
    step "Build project"

    export VORTEX_CONTAINER_REGISTRY_USER="${TEST_VORTEX_CONTAINER_REGISTRY_USER?Test Docker user is not set}"
    export VORTEX_CONTAINER_REGISTRY_PASS="${TEST_VORTEX_CONTAINER_REGISTRY_PASS?Test Docker pass is not set}"

    export VORTEX_PROVISION_POST_OPERATIONS_SKIP=1

    process_ahoyyml
    ahoy build
    sync_to_host
  fi

  popd >/dev/null || exit 1
}

setup_robo_fixture() {
  export HOME="${BUILD_DIR}"
  fixture_prepare_dir "${HOME}/.composer/vendor/bin"
  touch "${HOME}/.composer/vendor/bin/robo"
  chmod +x "${HOME}/.composer/vendor/bin/robo"
}

provision_docker_config_file() {
  export HOME="${BUILD_DIR}"
  fixture_prepare_dir "${HOME}/.docker"
  touch "${HOME}/.docker/config.json"
  echo "{$1:-docker.io}" > "${HOME}/.docker/config.json"
}
