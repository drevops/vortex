#!/usr/bin/env bash
#
# Helpers related to Vortex common testing functionality.
#
# In some cases, shell may report platform incorrectly. Run with forced platform:
# DOCKER_DEFAULT_PLATFORM=linux/amd64 bats --tap tests/bats/test.bats
#
# shellcheck disable=SC2155,SC2119,SC2120,SC2044,SC2294
#

################################################################################
#                       BATS HOOK IMPLEMENTATIONS                              #
################################################################################

setup() {
  # The root directory of the project.
  export ROOT_DIR="$(dirname "$(cd "$(dirname "${BATS_TEST_DIRNAME}")/.." && pwd)")"

  [ ! -d "${ROOT_DIR}/.vortex" ] && echo 'ERROR: The test should be run from the ".vortex" directory.' && exit 1

  ##
  ## Phase 1: Framework setup.
  ##

  # NOTE: If Docker tests fail, re-run with custom temporary directory
  # (must be pre-created): TMPDIR=${HOME}/.bats-tmp bats <testfile>'

  # Enforce architecture if not provided for ARM. Note that this may not work
  # if bash uses Rosetta or other emulators, in which case the test should run
  # with the variable explicitly set.
  # LCOV_EXCL_START
  if [ "$(uname -m)" = "arm64" ] || [ "$(uname -m)" = "aarch64" ]; then
    export DOCKER_DEFAULT_PLATFORM="${DOCKER_DEFAULT_PLATFORM:-linux/amd64}"
  fi

  if [ -n "${DOCKER_DEFAULT_PLATFORM:-}" ]; then
    if [ "${BATS_VERBOSE_RUN:-}" = "1" ] || [ "${TEST_VORTEX_DEBUG:-}" = "1" ]; then
      step "Using ${DOCKER_DEFAULT_PLATFORM} platform architecture."
    fi
  fi
  # LCOV_EXCL_STOP

  # Register a path to libraries.
  export BATS_LIB_PATH="${BATS_TEST_DIRNAME}/../node_modules"

  # Load 'bats-helpers' library.
  ASSERT_DIR_EXCLUDE=("vortex" ".data")
  export ASSERT_DIR_EXCLUDE
  bats_load_library bats-helpers

  # Setup command mocking.
  setup_mock

  ##
  ## Phase 2: Pre-flight checks.
  ##

  # Set test secrets.
  # For local test development, export these variables in your shell.
  export TEST_GITHUB_TOKEN="${TEST_GITHUB_TOKEN:-}"
  export TEST_VORTEX_CONTAINER_REGISTRY_USER="${TEST_VORTEX_CONTAINER_REGISTRY_USER:-}"
  export TEST_VORTEX_CONTAINER_REGISTRY_PASS="${TEST_VORTEX_CONTAINER_REGISTRY_PASS:-}"

  # The installer reference to use for tests.
  export TEST_INSTALLER_REF="${TEST_INSTALLER_REF:-main}"

  # Preflight checks.
  # @formatter:off
  command -v curl >/dev/null || (echo "[ERROR] curl command is not available." && exit 1)
  command -v composer >/dev/null || (echo "[ERROR] composer command is not available." && exit 1)
  command -v docker >/dev/null || (echo "[ERROR] docker command is not available." && exit 1)
  command -v ahoy >/dev/null || (echo "[ERROR] ahoy command is not available." && exit 1)
  command -v jq >/dev/null || (echo "[ERROR] jq command is not available." && exit 1)
  # @formatter:on

  ##
  ## Phase 3: Application test directories structure setup.
  ##

  # To run installation tests, several fixture directories are required.
  # They are defined and created in setup() test method.
  #
  # Root build directory where the rest of fixture directories located.
  # The "build" in this context is a place to store assets produced by the
  # installer script during the test.
  export BUILD_DIR="${BUILD_DIR:-"${BATS_TEST_TMPDIR//\/\//\/}/vortex-$(date +%s)"}"

  # Directory where the installer script is executed.
  # May have existing project files (e.g. from previous installations) or be
  # empty (to facilitate brand-new install).
  export CURRENT_PROJECT_DIR="${BUILD_DIR}/star_wars"

  # Directory where Vortex may be installed into.
  # By default, install uses ${CURRENT_PROJECT_DIR} as a destination, but we use
  # ${DST_PROJECT_DIR} to test a scenario where different destination is provided.
  export DST_PROJECT_DIR="${BUILD_DIR}/dst"

  # Directory where the installer script will be sourcing the instance of Vortex.
  # As a part of test setup, the local copy of Vortex at the last commit is
  # copied to this location. This means that during development of tests local
  # changes need to be committed.
  export LOCAL_REPO_DIR="${BUILD_DIR}/local_repo"

  # Directory where the application may store it's temporary files.
  export APP_TMP_DIR="${BUILD_DIR}/tmp"

  fixture_prepare_dir "${BUILD_DIR}"
  fixture_prepare_dir "${CURRENT_PROJECT_DIR}"
  fixture_prepare_dir "${DST_PROJECT_DIR}"
  fixture_prepare_dir "${LOCAL_REPO_DIR}"
  fixture_prepare_dir "${APP_TMP_DIR}"

  ##
  ## Phase 4: Application variables setup.
  ##

  # Isolate variables set in CI.
  unset VORTEX_DB_DOWNLOAD_SOURCE
  unset VORTEX_DB_IMAGE
  unset VORTEX_DB_DOWNLOAD_FORCE
  # Tokens required for tests are set explicitly within each tests with a TEST_ prefix.
  unset GITHUB_TOKEN
  unset VORTEX_CONTAINER_REGISTRY_USER
  unset VORTEX_CONTAINER_REGISTRY_PASS

  # Disable interactive prompts during tests.
  export AHOY_CONFIRM_RESPONSE=y
  # Disable waiting when interactive prompts are disabled durin tests.
  export AHOY_CONFIRM_WAIT_SKIP=1

  # Disable Doctor checks used on host machine.
  export VORTEX_DOCTOR_CHECK_TOOLS=0
  export VORTEX_DOCTOR_CHECK_PYGMY=0
  export VORTEX_DOCTOR_CHECK_PORT=0
  export VORTEX_DOCTOR_CHECK_SSH=0
  export VORTEX_DOCTOR_CHECK_WEBSERVER=0
  export VORTEX_DOCTOR_CHECK_BOOTSTRAP=0

  # Allow to override debug variables from environment when developing tests.
  export VORTEX_DEBUG="${TEST_VORTEX_DEBUG:-}"

  # Switch to using test demo DB.
  # Demo DB is what is being downloaded when the installer runs for the first
  # time do demonstrate downloading from CURL and importing from the DB dump
  # functionality.
  export VORTEX_INSTALL_DEMO_DB_TEST=https://github.com/drevops/vortex/releases/download/24.11.0/db_d11.test.sql

  ##
  ## Phase 5: SUT files setup.
  ##

  # Copy Vortex at the last commit.
  prepare_local_repo "${LOCAL_REPO_DIR}" >/dev/null

  # Prepare global git config and ignore files required for testing cleanup scenarios.
  prepare_global_gitconfig
  prepare_global_gitignore

  ##
  ## Phase 6: Setting debug mode.
  ##

  # Print debug if "--verbose-run" is passed or TEST_VORTEX_DEBUG is set to "1".
  if [ "${BATS_VERBOSE_RUN:-}" = "1" ] || [ "${TEST_VORTEX_DEBUG:-}" = "1" ]; then
    echo "Verbose run enabled." >&3
    echo "BUILD_DIR: ${BUILD_DIR}" >&3
    export RUN_STEPS_DEBUG=1
  fi

  # Change directory to the current project directory for each test. Tests
  # requiring to operate outside of CURRENT_PROJECT_DIR (like deployment tests)
  # should change directory explicitly within their tests.
  pushd "${CURRENT_PROJECT_DIR}" >/dev/null || exit 1
}

teardown() {
  restore_global_gitignore
  popd >/dev/null || cd "${ROOT_DIR}" || exit 1
}

################################################################################
#                               ASSERTIONS                                     #
################################################################################

assert_files_present() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local suffix_abbreviated="${3:-sw}"
  local suffix_abbreviated_camel_cased="${4:-Sw}"
  local suffix_camel_cased="${5:-StarWars}"

  assert_files_present_common "${dir}" "${suffix}" "${suffix_abbreviated}" "${suffix_abbreviated_camel_cased}" "${suffix_camel_cased}"

  assert_files_present_local "${dir}"

  # Assert Drupal profile not present by default.
  assert_files_present_no_profile "${dir}" "${suffix}"

  # Assert Drupal is not installed from the profile by default.
  assert_files_present_no_provision_use_profile "${dir}" "${suffix}"

  # Assert Drupal is not set to override an existing DB by default.
  assert_files_present_no_override_existing_db "${dir}" "${suffix}"

  # Assert deployments preserved.
  assert_files_present_deployment "${dir}" "${suffix}"

  # Assert Acquia integration is not preserved.
  assert_files_present_no_integration_acquia "${dir}" "${suffix}"

  # Assert Lagoon integration is not preserved.
  assert_files_present_no_integration_lagoon "${dir}" "${suffix}"

  # Assert FTP integration removed by default.
  assert_files_present_no_integration_ftp "${dir}" "${suffix}"

  # Assert renovatebot.com integration preserved.
  assert_files_present_integration_renovatebot "${dir}" "${suffix}"
}

assert_files_present_local() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1
  assert_file_exists ".env.local"
  popd >/dev/null || exit 1
}

assert_files_present_common() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local suffix_abbreviated="${3:-sw}"
  local suffix_abbreviated_camel_cased="${4:-Sw}"
  local suffix_camel_cased="${5:-StarWars}"
  local webroot="${6:-web}"
  local assert_theme="${7:-1}"

  local suffix_abbreviated_uppercase="$(string_to_upper "${suffix_abbreviated}")"

  pushd "${dir}" >/dev/null || exit 1

  # Default Vortex files present.
  assert_files_present_vortex "${dir}"

  # Assert that project name is correct.
  assert_file_contains .env "VORTEX_PROJECT=${suffix}"

  # Assert that Vortex version was replaced.
  assert_file_contains "README.md" "badge/Vortex-${TEST_VORTEX_VERSION:-develop}-65ACBC.svg"
  assert_file_contains "README.md" "https://github.com/drevops/vortex/tree/${TEST_VORTEX_VERSION:-develop}"
  assert_file_not_contains "README.md" "The following list includes"
  assert_file_not_exists "README.dist.md"

  # Assert that Vortex footnote remains.
  assert_file_contains "README.md" "This repository was created using the [Vortex](https://github.com/drevops/vortex) project template"

  assert_files_present_drupal "${dir}" "${suffix}" "${suffix_abbreviated}" "${suffix_abbreviated_camel_cased}" "${suffix_camel_cased}" "${webroot}" "${assert_theme}"

  popd >/dev/null || exit 1
}

assert_files_not_present_common() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-sw}"
  local suffix_abbreviated="${3:-sw}"
  local webroot="${5:-web}"

  pushd "${dir}" >/dev/null || exit 1

  assert_dir_not_exists "${webroot}/modules/custom/ys_base"
  assert_dir_not_exists "${webroot}/themes/custom/your_site_theme"
  assert_dir_not_exists "${webroot}/profiles/custom/${suffix}_profile"
  assert_dir_not_exists "${webroot}/modules/custom/${suffix_abbreviated}_base"
  assert_dir_not_exists "${webroot}/modules/custom/${suffix_abbreviated}_search"
  assert_dir_not_exists "${webroot}/themes/custom/${suffix}"
  assert_file_not_exists "${webroot}/sites/default/default.settings.local.php"
  assert_file_not_exists "${webroot}/sites/default/default.services.local.yml"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Unit/YourSiteExampleUnitTest.php"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Unit/YourSiteCoreUnitTestBase.php"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Kernel/YourSiteExampleKernelTest.php"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Kernel/YourSiteCoreKernelTestBase.php"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Functional/YourSiteExampleFunctionalTest.php"
  assert_file_not_exists "${webroot}/modules/custom/ys_base/tests/src/Functional/YourSiteCoreFunctionalTestBase.php"

  assert_file_not_exists "docs/faqs.md"
  assert_file_not_exists ".ahoy.yml"

  assert_file_not_exists "README.md"
  assert_file_not_exists ".github/workflows/build-test-deploy.yml"
  assert_file_not_exists ".circleci/config.yml"
  assert_file_not_exists "${webroot}/sites/default/settings.php"
  assert_file_not_exists "${webroot}/sites/default/services.yml"
  # Drupal Scaffold files not exist.
  assert_file_not_exists "${webroot}/.editorconfig"
  assert_file_not_exists "${webroot}/.eslintignore"
  assert_file_not_exists "${webroot}/.gitattributes"
  assert_file_not_exists "${webroot}/.htaccess"
  assert_file_not_exists "${webroot}/autoload.php"
  assert_file_not_exists "${webroot}/index.php"
  assert_file_not_exists "${webroot}/robots.txt"
  assert_file_not_exists "${webroot}/update.php"

  popd >/dev/null || exit 1
}

# These files should exist in every project.
assert_files_present_vortex() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".docker/cli.dockerfile"
  assert_file_exists ".docker/database.dockerfile"
  assert_file_exists ".docker/nginx-drupal.dockerfile"
  assert_file_exists ".docker/php.dockerfile"
  assert_file_exists ".docker/solr.dockerfile"
  assert_file_exists ".docker/scripts/.gitkeep"
  assert_file_exists ".docker/config/database/my.cnf"

  assert_file_exists ".docker/config/solr/config-set/accents_en.txt"
  assert_file_exists ".docker/config/solr/config-set/accents_und.txt"
  assert_file_exists ".docker/config/solr/config-set/elevate.xml"
  assert_file_exists ".docker/config/solr/config-set/protwords_en.txt"
  assert_file_exists ".docker/config/solr/config-set/protwords_und.txt"
  assert_file_exists ".docker/config/solr/config-set/schema.xml"
  assert_file_exists ".docker/config/solr/config-set/schema_extra_fields.xml"
  assert_file_exists ".docker/config/solr/config-set/schema_extra_types.xml"
  assert_file_exists ".docker/config/solr/config-set/solrconfig.xml"
  assert_file_exists ".docker/config/solr/config-set/solrconfig_extra.xml"
  assert_file_exists ".docker/config/solr/config-set/solrconfig_index.xml"
  assert_file_exists ".docker/config/solr/config-set/solrconfig_query.xml"
  assert_file_exists ".docker/config/solr/config-set/solrconfig_requestdispatcher.xml"
  assert_file_exists ".docker/config/solr/config-set/solrcore.properties"
  assert_file_exists ".docker/config/solr/config-set/stopwords_en.txt"
  assert_file_exists ".docker/config/solr/config-set/stopwords_und.txt"
  assert_file_exists ".docker/config/solr/config-set/synonyms_en.txt"
  assert_file_exists ".docker/config/solr/config-set/synonyms_und.txt"

  assert_file_exists ".github/PULL_REQUEST_TEMPLATE.md"

  assert_dir_exists "config/ci"
  assert_dir_exists "config/default"
  assert_dir_exists "config/dev"
  assert_dir_exists "config/local"
  assert_dir_exists "config/stage"

  assert_file_exists "patches/.gitkeep"

  assert_file_exists "scripts/composer/ScriptHandler.php"
  assert_file_exists "scripts/custom/.gitkeep"

  # Core Vortex files.
  assert_file_exists "scripts/vortex/deploy.sh"
  assert_file_exists "scripts/vortex/deploy-artifact.sh"
  assert_file_exists "scripts/vortex/deploy-container-registry.sh"
  assert_file_exists "scripts/vortex/deploy-lagoon.sh"
  assert_file_exists "scripts/vortex/deploy-webhook.sh"
  assert_file_exists "scripts/vortex/login-container-registry.sh"
  assert_file_exists "scripts/vortex/doctor.sh"
  assert_file_exists "scripts/vortex/download-db.sh"
  assert_file_exists "scripts/vortex/download-db-acquia.sh"
  assert_file_exists "scripts/vortex/download-db-url.sh"
  assert_file_exists "scripts/vortex/download-db-ftp.sh"
  assert_file_exists "scripts/vortex/download-db-container-registry.sh"
  assert_file_exists "scripts/vortex/download-db-lagoon.sh"
  assert_file_exists "scripts/vortex/export-db-file.sh"
  assert_file_exists "scripts/vortex/export-db-image.sh"
  assert_file_exists "scripts/vortex/provision.sh"
  assert_file_exists "scripts/vortex/login.sh"
  assert_file_exists "scripts/vortex/login-container-registry.sh"
  assert_file_exists "scripts/vortex/provision-sanitize-db.sh"
  assert_file_exists "scripts/vortex/info.sh"
  assert_file_exists "scripts/vortex/notify.sh"
  assert_file_exists "scripts/vortex/notify-email.sh"
  assert_file_exists "scripts/vortex/notify-github.sh"
  assert_file_exists "scripts/vortex/notify-jira.sh"
  assert_file_exists "scripts/vortex/notify-newrelic.sh"
  assert_file_exists "scripts/vortex/reset.sh"
  assert_file_exists "scripts/vortex/task-copy-db-acquia.sh"
  assert_file_exists "scripts/vortex/task-copy-files-acquia.sh"
  assert_file_exists "scripts/vortex/task-purge-cache-acquia.sh"
  assert_file_exists "scripts/vortex/update-vortex.sh"

  assert_file_exists "scripts/sanitize.sql"

  assert_file_exists "tests/behat/bootstrap/FeatureContext.php"
  assert_dir_exists "tests/behat/features"

  assert_file_exists ".ahoy.yml"
  assert_file_exists ".dockerignore"
  assert_file_exists ".editorconfig"
  assert_file_exists ".env"
  assert_file_not_exists ".gitattributes"
  assert_file_exists ".ahoy.local.example.yml"
  assert_file_exists ".env.local.example"
  assert_file_exists ".gitignore"
  assert_file_exists "behat.yml"
  assert_file_exists "composer.json"
  assert_file_exists "docker-compose.yml"
  assert_file_exists "gherkinlint.json"
  assert_file_exists "phpcs.xml"
  assert_file_exists "phpstan.neon"
  assert_file_exists "phpunit.xml"

  # Documentation information present.
  assert_file_exists "docs/faqs.md"
  assert_file_exists "README.md"
  assert_file_exists "docs/releasing.md"
  assert_file_exists "docs/testing.md"

  # Assert that Vortex files removed.
  assert_dir_not_exists ".vortex"
  assert_file_not_exists "LICENSE"
  assert_file_not_exists "CODE_OF_CONDUCT.md"
  assert_file_not_exists ".github/FUNDING.yml"

  assert_file_exists ".github/workflows/assign-author.yml"
  assert_file_exists ".github/workflows/label-merge-conflict.yml"
  assert_file_exists ".github/workflows/draft-release-notes.yml"

  assert_file_not_exists ".github/workflows/vortex-release-docs.yml"
  assert_file_not_exists ".github/workflows/vortex-test-docs.yml"
  assert_file_not_exists ".github/workflows/vortex-test-common.yml"
  assert_file_not_exists ".github/workflows/vortex-test-installer.yml"

  assert_file_not_contains ".circleci/config.yml" "vortex-dev-test"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-test-workflow"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-test-deployment"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-deploy"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-deploy-tags"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-didi-database-fi"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-database-ii"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-didi-build-fi"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-didi-build-ii"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-docs"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-didi-fi"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-didi-ii"
  assert_file_not_contains ".circleci/config.yml" "vortex-dev-installer"

  # Assert that documentation was processed correctly.
  assert_file_not_contains README.md "# Vortex"
  assert_dir_not_contains_string "${dir}" "/\.vortex"

  popd >/dev/null || exit 1
}

assert_files_present_drupal() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local suffix_abbreviated="${3:-sw}"
  local suffix_abbreviated_camel_cased="${4:-Sw}"
  local suffix_camel_cased="${5:-StarWars}"
  local webroot="${6:-web}"
  local assert_theme="${7:-1}"

  pushd "${dir}" >/dev/null || exit 1

  # Stub profile removed.
  assert_dir_not_exists "${webroot}/profiles/custom/your_site_profile"
  # Stub code module removed.
  assert_dir_not_exists "${webroot}/modules/custom/ys_base"
  # Stub theme removed.
  assert_dir_not_exists "${webroot}/themes/custom/your_site_theme"

  # Site core module created.
  assert_dir_exists "${webroot}/modules/custom/${suffix_abbreviated}_base"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/${suffix_abbreviated}_base.info.yml"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/${suffix_abbreviated}_base.module"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/${suffix_abbreviated}_base.deploy.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Unit/${suffix_abbreviated_camel_cased}BaseUnitTestBase.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Unit/ExampleTest.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Kernel/${suffix_abbreviated_camel_cased}BaseKernelTestBase.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Kernel/ExampleTest.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Functional/${suffix_abbreviated_camel_cased}BaseFunctionalTestBase.php"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_base/tests/src/Functional/ExampleTest.php"

  # Site search module created.
  assert_dir_exists "${webroot}/modules/custom/${suffix_abbreviated}_search"
  assert_file_exists "${webroot}/modules/custom/${suffix_abbreviated}_search/${suffix_abbreviated}_search.info.yml"

  # Site theme created.
  if [ "${assert_theme}" = 1 ]; then
    assert_dir_exists "${webroot}/themes/custom/${suffix}"
    assert_file_exists "${webroot}/themes/custom/${suffix}/js/${suffix}.js"
    assert_dir_exists "${webroot}/themes/custom/${suffix}/scss"
    assert_dir_exists "${webroot}/themes/custom/${suffix}/images"
    assert_dir_exists "${webroot}/themes/custom/${suffix}/fonts"
    assert_file_exists "${webroot}/themes/custom/${suffix}/.gitignore"
    assert_file_exists "${webroot}/themes/custom/${suffix}/${suffix}.info.yml"
    assert_file_exists "${webroot}/themes/custom/${suffix}/${suffix}.libraries.yml"
    assert_file_exists "${webroot}/themes/custom/${suffix}/${suffix}.theme"
    assert_file_exists "${webroot}/themes/custom/${suffix}/Gruntfile.js"
    assert_file_exists "${webroot}/themes/custom/${suffix}/package.json"

    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Unit/${suffix_camel_cased}UnitTestBase.php"
    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Unit/ExampleTest.php"
    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Kernel/${suffix_camel_cased}KernelTestBase.php"
    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Kernel/ExampleTest.php"
    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Functional/${suffix_camel_cased}FunctionalTestBase.php"
    assert_file_exists "${webroot}/themes/custom/${suffix}/tests/src/Functional/ExampleTest.php"

    # Comparing binary files.
    assert_binary_files_equal "${LOCAL_REPO_DIR}/web/themes/custom/your_site_theme/screenshot.png" "${webroot}/themes/custom/${suffix}/screenshot.png"
  fi

  # Drupal Scaffold files exist.
  assert_file_not_exists "${webroot}/.editorconfig"
  assert_file_not_exists "${webroot}/.eslintignore"
  assert_file_not_exists "${webroot}/.eslintrc.json"
  assert_file_not_exists "${webroot}/.gitattributes"
  assert_file_exists "${webroot}/.htaccess"
  assert_file_exists "${webroot}/autoload.php"
  assert_file_exists "${webroot}/index.php"
  assert_file_exists "${webroot}/robots.txt"
  assert_file_exists "${webroot}/update.php"

  # Settings files exist.
  # @note The permissions can be 644 or 664 depending on the umask of OS. Also,
  # git only track 644 or 755.
  assert_file_exists "${webroot}/sites/default/settings.php"
  assert_file_mode "${webroot}/sites/default/settings.php" "644"

  assert_dir_exists "${webroot}/sites/default/includes/"

  assert_file_exists "${webroot}/sites/default/default.settings.php"
  assert_file_exists "${webroot}/sites/default/default.services.yml"

  assert_file_exists "${webroot}/sites/default/default.settings.local.php"
  assert_file_mode "${webroot}/sites/default/default.settings.local.php" "644"

  assert_file_exists "${webroot}/sites/default/default.services.local.yml"
  assert_file_mode "${webroot}/sites/default/default.services.local.yml" "644"

  # Special case to fix all occurrences of the stub in core files to exclude
  # false-positives from the assertions below.
  replace_drupal_core_stubs "${dir}" "your_site" "${webroot}"

  # Assert all stub strings were replaced.
  assert_dir_not_contains_string "${dir}" "your_site"
  assert_dir_not_contains_string "${dir}" "ys_base"
  assert_dir_not_contains_string "${dir}" "YOURSITE"
  assert_dir_not_contains_string "${dir}" "YourSite"
  assert_dir_not_contains_string "${dir}" "your_site_theme"
  assert_dir_not_contains_string "${dir}" "your_org"
  assert_dir_not_contains_string "${dir}" "YOURORG"
  assert_dir_not_contains_string "${dir}" "www.your-site-domain.example"
  # Assert all special comments were removed.
  assert_dir_not_contains_string "${dir}" "#;"
  assert_dir_not_contains_string "${dir}" "#;<"
  assert_dir_not_contains_string "${dir}" "#;>"

  popd >/dev/null || exit 1
}

assert_files_present_profile() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local webroot="${3:-web}"

  pushd "${dir}" >/dev/null || exit 1

  # Site profile created.
  assert_dir_exists "${webroot}/profiles/custom/${suffix}_profile"
  assert_file_exists "${webroot}/profiles/custom/${suffix}_profile/${suffix}_profile.info.yml"
  assert_file_contains ".env" "DRUPAL_PROFILE="

  popd >/dev/null || exit 1
}

assert_files_present_no_profile() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local webroot="${3:-web}"

  pushd "${dir}" >/dev/null || exit 1

  # Site profile created.
  assert_dir_not_exists "${webroot}/profiles/custom/${suffix}_profile"
  assert_file_contains ".env" "DRUPAL_PROFILE=standard"
  assert_file_not_contains ".env" "${webroot}/profiles/custom/${suffix}_profile,"
  # Assert that there is no renaming of the custom profile with core profile name.
  assert_dir_not_exists "${webroot}/profiles/custom/standard"
  assert_file_not_contains ".env" "${webroot}/profiles/custom/standard,"

  popd >/dev/null || exit 1
}

assert_files_present_provision_use_profile() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains ".env" "VORTEX_PROVISION_TYPE=profile"
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_SOURCE"
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_CURL_URL"
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_ENVIRONMENT"

  assert_file_not_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FORCE"
  assert_file_not_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_USER"
  assert_file_not_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_PASS"
  assert_file_not_contains ".env.local.example" "VORTEX_ACQUIA_KEY"
  assert_file_not_contains ".env.local.example" "VORTEX_ACQUIA_SECRET"
  assert_file_not_contains ".env.local.example" "VORTEX_CONTAINER_REGISTRY_USER"
  assert_file_not_contains ".env.local.example" "VORTEX_CONTAINER_REGISTRY_PASS"

  assert_file_exists ".ahoy.yml"
  assert_file_not_contains ".ahoy.yml" "download-db:"

  assert_file_not_contains "README.md" "ahoy download-db"

  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "database:"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "schedule:"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_TIMESTAMP"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_FALLBACK"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_BRANCH"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "Save DB cache"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "Restore DB cache"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "Create cache keys files for database caching"
  assert_file_not_contains ".github/workflows/build-test-deploy.yml" "Show cache key for database caching"

  assert_file_not_contains ".circleci/config.yml" "db_ssh_fingerprint"
  assert_file_not_contains ".circleci/config.yml" "/root/project/.data"
  assert_file_not_contains ".circleci/config.yml" "nightly_db_schedule"
  assert_file_not_contains ".circleci/config.yml" "VORTEX_DB_DOWNLOAD_SSH_FINGERPRINT"
  assert_file_not_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_TIMESTAMP"
  assert_file_not_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_FALLBACK"
  assert_file_not_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_BRANCH"
  assert_file_not_contains ".circleci/config.yml" "database: &job-database"
  assert_file_not_contains ".circleci/config.yml" "database-nightly"
  assert_file_not_contains ".circleci/config.yml" "name: Set cache keys for database caching"
  assert_file_not_contains ".circleci/config.yml" "- database:"

  popd >/dev/null || exit 1
}

assert_files_present_no_provision_use_profile() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains ".env" "VORTEX_PROVISION_TYPE=database"

  assert_file_exists ".ahoy.yml"
  assert_file_contains ".ahoy.yml" "download-db:"

  assert_file_contains "README.md" "ahoy download-db"

  if [ -f ".github/workflows/build-test-deploy.yml"  ]; then
    assert_file_contains ".github/workflows/build-test-deploy.yml" "database:"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "schedule:"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_TIMESTAMP"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_FALLBACK"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "VORTEX_CI_DB_CACHE_BRANCH"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "Save DB cache"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "Restore DB cache"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "Create cache keys files for database caching"
    assert_file_contains ".github/workflows/build-test-deploy.yml" "Show cache key for database caching"
  fi

  if [ -f ".circleci/config.yml" ]; then
    assert_file_contains ".circleci/config.yml" "db_ssh_fingerprint"
    assert_file_contains ".circleci/config.yml" "/root/project/.data"
    assert_file_contains ".circleci/config.yml" "nightly_db_schedule"
    assert_file_contains ".circleci/config.yml" "VORTEX_DB_DOWNLOAD_SSH_FINGERPRINT"
    assert_file_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_TIMESTAMP"
    assert_file_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_FALLBACK"
    assert_file_contains ".circleci/config.yml" "VORTEX_CI_DB_CACHE_BRANCH"
    assert_file_contains ".circleci/config.yml" "database: &job-database"
    assert_file_contains ".circleci/config.yml" "database-nightly"
    assert_file_contains ".circleci/config.yml" "name: Set cache keys for database caching"
    assert_file_contains ".circleci/config.yml" "- database:"
  fi

  popd >/dev/null || exit 1
}

assert_files_present_override_existing_db() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains ".env" "VORTEX_PROVISION_OVERRIDE_DB=1"

  popd >/dev/null || exit 1
}

assert_files_present_no_override_existing_db() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains ".env" "VORTEX_PROVISION_OVERRIDE_DB=0"

  popd >/dev/null || exit 1
}

assert_files_present_ci_provider_gha() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".github/workflows/build-test-deploy.yml"
  assert_file_contains "README.md" "[![Database, Build, Test and Deploy"
  assert_file_contains "README.md" "docs/ci.md"
  assert_file_contains "docs/ci.md" "GitHub Actions"

  assert_files_present_no_ci_provider_circleci "$dir" "$suffix"

  popd >/dev/null || exit 1
}

assert_files_present_no_ci_provider_gha() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".github/workflows/build-test-deploy.yml"
  assert_file_not_contains "README.md" "[![Database, Build, Test and Deploy"
  assert_file_not_contains "docs/ci.md" "GitHub Actions"

  popd >/dev/null || exit 1
}

assert_files_present_ci_provider_circleci() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".circleci/config.yml"
  assert_file_contains "README.md" "[![CircleCI"
  assert_file_contains "README.md" "docs/ci.md"
  assert_file_contains "docs/ci.md" "CircleCI"

  assert_files_present_no_ci_provider_gha "$dir" "$suffix"

  popd >/dev/null || exit 1
}

assert_files_present_no_ci_provider_circleci() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".circleci/config.yml"
  assert_file_not_contains "README.md" "[![CircleCI"
  assert_file_not_contains "docs/ci.md" "CircleCI"

  popd >/dev/null || exit 1
}

assert_files_present_ci_provider_none() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_files_present_no_ci_provider_gha "$dir" "$suffix"
  assert_files_present_no_ci_provider_circleci "$dir" "$suffix"

  assert_file_not_exists "docs/ci.md"
  assert_file_not_contains "README.md" "docs/ci.md"

  popd >/dev/null || exit 1
}

assert_files_present_deployment() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists "docs/deployment.md"

  if [ -f ".github/workflows/build-test-deploy.yml" ]; then
    assert_file_contains ".github/workflows/build-test-deploy.yml" "deploy:"
  fi

  if [ -f ".circleci/config.yml" ]; then
    assert_file_contains ".circleci/config.yml" "deploy: &job_deploy"
    assert_file_contains ".circleci/config.yml" "deploy-tags: &job-deploy-tags"
  fi

  popd >/dev/null || exit 1
}

assert_files_present_no_deployment() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local has_committed_files="${3:-0}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".gitignore.artifact"
  assert_file_not_exists "docs/deployment.md"

  # 'Required' files can be asserted for modifications only if they were not
  # committed.
  if [ "${has_committed_files:-}" -eq 0 ]; then
    assert_file_not_contains ".github/workflows/build-test-deploy.yml" "deploy:"

    assert_file_not_contains ".circleci/config.yml" "deploy: &job_deploy"
    assert_file_not_contains ".circleci/config.yml" "deploy-tags: &job-deploy-tags"
    assert_file_not_contains ".circleci/config.yml" "- deploy:"
    assert_file_not_contains ".circleci/config.yml" "- deploy-tags:"
  fi

  popd >/dev/null || exit 1
}

assert_files_present_integration_acquia() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-sw}"
  local include_scripts="${3:-1}"
  local webroot="${4:-web}"

  pushd "${dir}" >/dev/null || exit 1

  assert_dir_exists "hooks"
  assert_dir_exists "hooks/library"
  assert_file_mode "hooks/library/copy-db.sh" "755"
  assert_file_mode "hooks/library/copy-files.sh" "755"
  assert_file_mode "hooks/library/provision.sh" "755"
  assert_file_mode "hooks/library/notify-deployment.sh" "755"
  assert_file_mode "hooks/library/purge-cache.sh" "755"

  assert_dir_exists "hooks/common"
  assert_dir_exists "hooks/common/post-code-update"
  assert_symlink_exists "hooks/common/post-code-update/1.provision.sh"
  assert_symlink_exists "hooks/common/post-code-update/2.purge-cache.sh"
  assert_symlink_exists "hooks/common/post-code-update/3.notify-deployment.sh"
  assert_symlink_exists "hooks/common/post-code-deploy"
  assert_symlink_exists "hooks/common/post-db-copy/1.provision.sh"
  assert_symlink_exists "hooks/common/post-db-copy/2.purge-cache.sh"
  assert_symlink_exists "hooks/common/post-db-copy/3.notify-deployment.sh"

  assert_dir_exists "hooks/prod"
  assert_dir_exists "hooks/prod/post-code-deploy"
  assert_symlink_exists "hooks/prod/post-code-update"
  assert_symlink_not_exists "hooks/prod/post-db-copy"

  assert_file_exists "${webroot}/sites/default/includes/providers/settings.acquia.php"
  assert_file_contains "${webroot}/.htaccess" "RewriteCond %{HTTP_HOST} !\.acquia-sites\.com [NC]"

  if [ "${include_scripts:-}" -eq 1 ]; then
    assert_dir_exists "scripts"
    assert_file_contains ".env" "VORTEX_ACQUIA_APP_NAME="
    assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_ENVIRONMENT="
    assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="
  fi

  popd >/dev/null || exit 1
}

assert_files_present_no_integration_acquia() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local webroot="${3:-web}"

  pushd "${dir}" >/dev/null || exit 1

  assert_dir_not_exists "hooks"
  assert_dir_not_exists "hooks/library"
  assert_file_not_exists "${webroot}sites/default/includes/providers/settings.acquia.php"
  assert_file_not_contains "${webroot}/.htaccess" "RewriteCond %{HTTP_HOST} !\.acquia-sites\.com [NC]"
  assert_file_not_contains ".env" "VORTEX_ACQUIA_APP_NAME="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="
  assert_file_not_contains ".ahoy.yml" "VORTEX_ACQUIA_APP_NAME="
  assert_file_not_contains ".ahoy.yml" "VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="
  assert_dir_not_contains_string "${dir}" "VORTEX_ACQUIA_KEY"
  assert_dir_not_contains_string "${dir}" "VORTEX_ACQUIA_SECRET"

  popd >/dev/null || exit 1
}

assert_files_present_integration_lagoon() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local webroot="${3:-web}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists "${webroot}/sites/default/includes/providers/settings.lagoon.php"
  assert_file_exists ".lagoon.yml"
  assert_file_exists "drush/sites/lagoon.site.yml"
  assert_file_exists ".github/workflows/close-pull-request.yml"
  assert_file_contains "docker-compose.yml" "labels"
  assert_file_contains "docker-compose.yml" "lagoon.type: cli-persistent"
  assert_file_contains "docker-compose.yml" "lagoon.persistent.name: &lagoon-nginx-name nginx-php"
  assert_file_contains "docker-compose.yml" "lagoon.persistent: &lagoon-persistent-files /app/web/sites/default/files/"
  assert_file_contains "docker-compose.yml" "lagoon.type: nginx-php-persistent"
  assert_file_contains "docker-compose.yml" "lagoon.name: *lagoon-nginx-name"
  assert_file_contains "docker-compose.yml" "lagoon.type: mariadb"
  assert_file_contains "docker-compose.yml" "lagoon.type: solr"
  assert_file_contains "docker-compose.yml" "lagoon.type: none"

  popd >/dev/null || exit 1
}

assert_files_present_no_integration_lagoon() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"
  local webroot="${3:-web}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists "${webroot}/sites/default/includes/providers/settings.lagoon.php"
  assert_file_not_exists ".lagoon.yml"
  assert_file_not_exists "drush/sites/lagoon.site.yml"
  assert_file_not_exists ".github/workflows/close-pull-request.yml"
  assert_file_not_contains "docker-compose.yml" "labels"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: cli-persistent"
  assert_file_not_contains "docker-compose.yml" "lagoon.persistent.name: &lagoon-nginx-name nginx-php"
  assert_file_not_contains "docker-compose.yml" "lagoon.persistent: &lagoon-persistent-files /app/web/sites/default/files/"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: nginx-php-persistent"
  assert_file_not_contains "docker-compose.yml" "lagoon.name: *lagoon-nginx-name"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: mariadb"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: solr"
  assert_file_not_contains "docker-compose.yml" "lagoon.type: none"

  popd >/dev/null || exit 1
}

assert_files_present_integration_ftp() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_HOST="
  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_PORT="
  assert_file_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_FILE="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_USER="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_PASS="
  assert_file_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_USER="
  assert_file_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_PASS="

  popd >/dev/null || exit 1
}

assert_files_present_no_integration_ftp() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_HOST="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_PORT="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_FILE="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_USER="
  assert_file_not_contains ".env" "VORTEX_DB_DOWNLOAD_FTP_PASS="
  assert_file_not_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_USER="
  assert_file_not_contains ".env.local.example" "VORTEX_DB_DOWNLOAD_FTP_PASS="

  popd >/dev/null || exit 1
}

assert_files_present_integration_renovatebot() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains "README.md" "Automated updates"

  assert_file_exists "renovate.json"

  if [ -f ".circleci/config.yml" ]; then
    assert_file_contains ".circleci/config.yml" "renovatebot-self-hosted"
    assert_file_contains ".circleci/config.yml" "renovatebot_schedule"
  fi

  # @todo Add assertions for self-hosted Renovatebot in GitHub Actions.

  popd >/dev/null || exit 1
}

assert_files_present_no_integration_renovatebot() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_contains "README.md" "Automated updates"

  assert_file_not_exists "renovate.json"

  assert_file_not_contains ".circleci/config.yml" "renovatebot-self-hosted"
  assert_file_not_contains ".circleci/config.yml" "renovatebot_schedule"

  # @todo Add assertions for self-hosted Renovatebot in GitHub Actions.

  popd >/dev/null || exit 1
}

assert_files_present_onboarding() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_contains "docs/README.md" "Onboarding"
  assert_file_contains "README.md" "Onboarding"

  assert_file_exists "docs/onboarding.md"

  popd >/dev/null || exit 1
}

assert_files_present_no_onboarding() {
  local dir="${1:-$(pwd)}"
  local suffix="${2:-star_wars}"

  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_contains "docs/README.md" "Onboarding"
  assert_file_not_contains "README.md" "Onboarding"

  assert_file_not_exists "docs/onboarding.md"

  popd >/dev/null || exit 1
}

assert_webpage_contains() {
  path="${1}"
  content="${2}"
  t=$(mktemp)
  ahoy cli curl -L -s "http://nginx:8080${path}" >"${t}"
  assert_file_contains "${t}" "${content}"
}

assert_webpage_not_contains() {
  path="${1}"
  content="${2}"
  t=$(mktemp)
  ahoy cli curl -L -s "http://nginx:8080${path}" >"${t}"
  assert_file_not_contains "${t}" "${content}"
}

create_fixture_readme() {
  local dir="${1:-$(pwd)}"
  local name="${2:-Star Wars}"
  local org="${3:-Star Wars Org}"

  cat <<EOT >>"${dir}"/README.md
# ${name}
Drupal 11 implementation of ${name} for ${org}

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex-VORTEX_VERSION_URLENCODED-65ACBC.svg)](https://github.com/drevops/vortex/tree/VORTEX_VERSION)

some other text
EOT
}

create_fixture_composerjson() {
  local name="${1}"
  local machine_name="${2}"
  local org="${3}"
  local org_machine_name="${4}"
  local dir="${5:-$(pwd)}"

  cat <<EOT >>"${dir}"/composer.json
{
    "name": "${org_machine_name}/${machine_name}",
    "description": "Drupal 11 implementation of ${name} for ${org}"
}
EOT
}

################################################################################
#                               UTILITIES                                      #
################################################################################

# Run the installer script.
# shellcheck disable=SC2120
run_installer_quiet() {
  pushd "${CURRENT_PROJECT_DIR}" >/dev/null || exit 1

  # Force the installer script to be downloaded from the local repo for testing.
  export VORTEX_INSTALL_REPO="${VORTEX_INSTALL_REPO:-${LOCAL_REPO_DIR}}"

  # Use unique installer temporary directory for each run. This is where
  # the installer script downloads the Vortex codebase for processing.
  VORTEX_INSTALL_TMP_DIR="${APP_TMP_DIR}/$(random_string)"
  fixture_prepare_dir "${VORTEX_INSTALL_TMP_DIR}"
  export VORTEX_INSTALL_TMP_DIR

  # Tests are using demo database and 'ahoy download-db' command, so we need
  # to set the CURL DB to test DB.
  #
  # Override demo database with test demo database. This is required to use
  # test assertions ("star wars") with demo database.
  #
  # Installer will load environment variable and it will take precedence over
  # the value in .env file.
  export VORTEX_DB_DOWNLOAD_URL="${VORTEX_INSTALL_DEMO_DB_TEST}"

  opt_no_interaction="--no-interaction"
  [ "${TEST_RUN_INSTALL_INTERACTIVE:-}" = "1" ] && opt_no_interaction=""

  [ ! -d "${ROOT_DIR}/.vortex/installer/vendor" ] && composer --working-dir="${ROOT_DIR}/.vortex/installer" install

  # Run the installer script from the local repository to allow debugging.
  run php "${ROOT_DIR}/.vortex/installer/install" "${opt_no_interaction}" "$@"

  # Special treatment for cases where volumes are not mounted from the host.
  fix_host_dependencies "$@"

  popd >/dev/null || exit 1

  # Print the output of the installer script. This, however, makes error logs
  # harder to read.
  # shellcheck disable=SC2154
  echo "${output}"
}

#
# Create a stub of installed dependencies.
#
# Used for fast unit testing of the installer functionality.
#
install_dependencies_stub() {
  local dir="${1:-$(pwd)}"
  local webroot="${2:-web}"

  pushd "${dir}" >/dev/null || exit 1

  mktouch "${webroot}/core/.vortex/installer/install"
  mktouch "${webroot}/modules/contrib/somemodule/somemodule.info.yml"
  mktouch "${webroot}/themes/contrib/sometheme/sometheme.info.yml"
  mktouch "${webroot}/profiles/contrib/someprofile/someprofile.info.yml"
  mktouch "${webroot}/sites/default/somesettingsfile.php"
  mktouch "${webroot}/sites/default/files/somepublicfile.php"
  mktouch "vendor/somevendor/somepackage/somepackage.php"
  mktouch "vendor/somevendor/somepackage/somepackage with spaces.php"
  mktouch "vendor/somevendor/somepackage/composer.json"
  mktouch "${webroot}/themes/custom/zzzsomecustomtheme/node_modules/somevendor/somepackage/somepackage.js"

  mktouch "${webroot}/modules/themes/custom/zzzsomecustomtheme/build/js/zzzsomecustomtheme.min.js"
  mktouch ".logs/screenshots/s1.jpg"
  mktouch ".data/db.sql"

  mktouch "${webroot}/sites/default/settings.local.php"
  mktouch "${webroot}/sites/default/services.local.yml"
  echo 'version: "2.3"' >"docker-compose.override.yml"

  popd >/dev/null || exit 1
}

replace_drupal_core_stubs() {
  local dir="${1}"
  local token="${2}"
  local webroot="${3:-web}"

  replace_string_content "${token}" "some_other_site" "${dir}/${webroot}"
}

create_development_settings() {
  local webroot="${1:-web}"
  substep "Create development settings"
  assert_file_not_exists "${webroot}/sites/default/settings.local.php"
  assert_file_not_exists "${webroot}/sites/default/services.local.yml"
  assert_file_exists "${webroot}/sites/default/default.settings.local.php"
  assert_file_exists "${webroot}/sites/default/default.services.local.yml"
  cp "${webroot}/sites/default/default.settings.local.php" "${webroot}/sites/default/settings.local.php"
  cp "${webroot}/sites/default/default.services.local.yml" "${webroot}/sites/default/services.local.yml"
  assert_file_exists "${webroot}/sites/default/settings.local.php"
  assert_file_exists "${webroot}/sites/default/services.local.yml"
}

remove_development_settings() {
  local webroot="${1:-web}"
  substep "Remove development settings"
  rm -f "${webroot}/sites/default/settings.local.php" || true
  rm -f "${webroot}/sites/default/services.local.yml" || true
}

# Prepare local repository from the current codebase.
prepare_local_repo() {
  local dir="${1:-$(pwd)}"
  local do_copy_code="${2:-1}"
  local commit

  if [ "${do_copy_code:-}" -eq 1 ]; then
    fixture_prepare_dir "${dir}"
    export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1
    fixture_export_codebase "${dir}" "${ROOT_DIR}"
  fi

  git_init 0 "${dir}"
  [ "$(git config --global user.name)" = "" ] && echo "Configuring global test git user name." && git config --global user.name "Some User"
  [ "$(git config --global user.email)" = "" ] && echo "Configuring global test git user email." && git config --global user.email "some.user@example.com"
  commit=$(git_add_all_commit "Initial commit" "${dir}")

  echo "${commit}"
}

prepare_global_gitconfig() {
  git config --global init.defaultBranch >/dev/null || git config --global init.defaultBranch "main"
}

prepare_global_gitignore() {
  filename="${HOME}/.gitignore"
  filename_backup="${filename}".bak

  if git config --global --list | grep -q core.excludesfile; then
    git config --global core.excludesfile >/tmp/git_config_global_exclude
  fi

  [ -f "${filename}" ] && cp "${filename}" "${filename_backup}"

  cat <<EOT >"${filename}"
##
## Temporary files generated by various OSs and IDEs
##
Thumbs.db
._*
.DS_Store
.idea
.idea/*
*.sublime*
.project
.netbeans
.vscode
.vscode/*
nbproject
nbproject/*
EOT

  git config --global core.excludesfile "${filename}"
}

restore_global_gitignore() {
  filename=${HOME}/.gitignore
  filename_backup="${filename}".bak
  [ -f "${filename_backup}" ] && cp "${filename_backup}" "${filename}"
  [ -f "/tmp/git_config_global_exclude" ] && git config --global core.excludesfile "$(cat /tmp/git_config_global_exclude)"
}

git_add() {
  local file="${1}"
  local dir="${2:-$(pwd)}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add "${dir}/${file}" >/dev/null
}

git_add_force() {
  local file="${1}"
  local dir="${2:-$(pwd)}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" add -f "${dir}/${file}" >/dev/null
}

git_commit() {
  local message="${1}"
  local dir="${2:-$(pwd)}"

  assert_git_repo "${dir}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" >/dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_add_all_commit() {
  local message="${1}"
  local dir="${2:-$(pwd)}"

  assert_git_repo "${dir}"

  git --work-tree="${dir}" --git-dir="${dir}/.git" add -A
  git --work-tree="${dir}" --git-dir="${dir}/.git" commit -m "${message}" >/dev/null
  commit=$(git --work-tree="${dir}" --git-dir="${dir}/.git" rev-parse HEAD)
  echo "${commit}"
}

git_init() {
  local allow_receive_update="${1:-0}"
  local dir="${2:-$(pwd)}"

  assert_not_git_repo "${dir}"
  git --work-tree="${dir}" --git-dir="${dir}/.git" init >/dev/null

  if [ "${allow_receive_update:-}" -eq 1 ]; then
    git --work-tree="${dir}" --git-dir="${dir}/.git" config receive.denyCurrentBranch updateInstead >/dev/null
  fi
}

# Replace string content in the directory.
replace_string_content() {
  local needle="${1}"
  local replacement="${2}"
  local dir="${3}"
  local sed_opts

  sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

  set +e
  grep -rI \
    --exclude-dir=".git" \
    --exclude-dir=".idea" \
    --exclude-dir="vendor" \
    --exclude-dir="node_modules" \
    --exclude-dir=".data" \
    -l "${needle}" "${dir}" |
    xargs sed "${sed_opts[@]}" "s@${needle}@${replacement}@g" || true
  set -eu
}

string_to_upper() {
  echo "$@" | tr '[:lower:]' '[:upper:]'
}

# Print step.
step() {
  # Using prefix different from command prefix in SUT for easy debug.
  echo "**> STEP: ${1}" >&3
}

# Print sub-step.
substep() {
  echo "     > ${1}" >&3
}

# Sync files to host in case if volumes are not mounted from host.
sync_to_host() {
  local dst="${1:-.}"
  # shellcheck disable=SC1090,SC1091
  [ -f "./.env" ] && t=$(mktemp) && export -p >"${t}" && set -a && . "./.env" && set +a && . "${t}" && rm "${t}" && unset t
  [ "${VORTEX_DEV_VOLUMES_SKIP_MOUNT:-0}" != "1" ] && return
  docker compose cp -L cli:/app/. "${dst}"
}

# Sync files to container in case if volumes are not mounted from host.
sync_to_container() {
  local src="${1:-.}"
  # shellcheck disable=SC1090,SC1091
  [ -f "./.env" ] && t=$(mktemp) && export -p >"${t}" && set -a && . "./.env" && set +a && . "${t}" && rm "${t}" && unset t
  [ "${VORTEX_DEV_VOLUMES_SKIP_MOUNT:-0}" != "1" ] && return
  docker compose cp -L "${src}" cli:/app/
}

# Special treatment for cases where volumes are not mounted from the host.
fix_host_dependencies() {
  # Replicate behaviour of .vortex/installer/install script to extract destination directory
  # passed as an argument.
  # shellcheck disable=SC2235
  ([ "${1:-}" = "--quiet" ] || [ "${1:-}" = "-q" ]) && shift
  # Destination directory, that can be overridden with the first argument to this script.
  VORTEX_INSTALL_DST_DIR="${VORTEX_INSTALL_DST_DIR:-$(pwd)}"
  VORTEX_INSTALL_DST_DIR=${1:-${VORTEX_INSTALL_DST_DIR}}

  pushd "${VORTEX_INSTALL_DST_DIR}" >/dev/null || exit 1

  if [ -f docker-compose.yml ] && [ "${VORTEX_DEV_VOLUMES_SKIP_MOUNT:-0}" = "1" ]; then
    sed -i -e "/###/d" docker-compose.yml
    assert_file_not_contains docker-compose.yml "###"
    sed -i -e "s/##//" docker-compose.yml
    assert_file_not_contains docker-compose.yml "##"
  fi

  popd >/dev/null || exit 1
}

##
# Creates a wrapper script for a globally available binary.
#
# Creates a wrapper script for a globally available binary in a specified
# directory and filename.
# This allows scripts that reference binaries in the specified directory to use
# the global command.
#
# Parameters:
#   - path_with_bin
#     The full path where the wrapper should be created
#     (e.g., "vendor/bin/custom_drush").
#   - global_bin (optional)
#     The name of the global binary for which the wrapper is being created.
#     Defaults to the bin name from path_with_bin if not provided.
#
# Usage:
#   create_global_command_wrapper "vendor/bin/custom_drush"  # uses "custom_drush" as global_bin
#   create_global_command_wrapper "vendor/bin/custom_drush" "drush"  # uses "drush" as global_bin
create_global_command_wrapper() {
  local path_with_bin="${1}"
  local global_bin="${2:-$(basename "${path_with_bin}")}"
  mkdir -p "$(dirname "${path_with_bin}")"
  cat <<EOL >"${path_with_bin}"
#!/bin/bash
${global_bin} "\$@"
EOL
  chmod +x "${path_with_bin}"
}

download_installer() {
  pushd "${1}" >/dev/null || exit 1

  rm -Rf "install.php" >/dev/null || true



  composer install --no-progress --no-suggest > /dev/null 2>&1
  composer build > /dev/null 2>&1

  cp .build/installer "install.php" > /dev/null

  assert_file_exists "install.php"

  popd >/dev/null || exit 1
}

process_ahoyyml() {
  [ "${VORTEX_DEV_VOLUMES_SKIP_MOUNT:-0}" != "1" ] && return

  # Override the provision command in .ahoy.yml to copy the database file to
  # the container for when the volumes are not mounted.
  # We are doing this only to replicate developer's workflow and experience
  # when they run `ahoy build` locally.
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')
  sed "${sed_opts[@]}" 's|cmd: ahoy cli ./scripts/vortex/provision.sh|cmd: if [ -f .data/db.sql ]; then docker compose exec cli mkdir -p .data; docker compose cp -L .data/db.sql cli:/app/.data/db.sql; fi; ahoy cli \.\/scripts\/vortex\/provision\.sh|g' .ahoy.yml
}

setup_ssh_key_fixture() {
  export HOME="${BUILD_DIR}"
  export SSH_KEY_FIXTURE_DIR="${BUILD_DIR}/.ssh"
  fixture_prepare_dir "${SSH_KEY_FIXTURE_DIR}"
}

provision_default_ssh_key() {
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa" >/dev/null
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_TEST" >/dev/null
}

provision_ssh_key_with_suffix() {
  local suffix="${1:-TEST}"
  ssh-keygen -t rsa -b 4096 -C "" -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" >/dev/null
}
