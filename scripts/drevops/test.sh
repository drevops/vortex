#!/usr/bin/env bash
# shellcheck disable=SC2015
##
# Run tests.
#
# Usage:
# Run all tests:
# ./test.sh
#
# Run unit tests:
# DREVOPS_TEST_TYPE=unit ./test.sh
#
# Run kernel tests:
# DREVOPS_TEST_TYPE=kernel ./test.sh
#
# Run functional tests:
# DREVOPS_TEST_TYPE=functional ./test.sh
#
# Run bdd tests:
# DREVOPS_TEST_TYPE=bdd ./test.sh
#
# Run specific tags (eg: content_type) bdd tests:
# DREVOPS_TEST_TYPE=bdd DREVOPS_TEST_BEHAT_TAGS=content_type ./test.sh

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip running of all tests.
# Helpful to set in CI to skip running of tests without modifying the codebase.
DREVOPS_TEST_SKIP="${DREVOPS_TEST_SKIP:-}"

# Flag to allow Unit tests to fail.
DREVOPS_TEST_UNIT_ALLOW_FAILURE="${DREVOPS_TEST_UNIT_ALLOW_FAILURE:-0}"

# Unit test group. Optional. Default runs all tests.
DREVOPS_TEST_UNIT_GROUP="${DREVOPS_TEST_UNIT_GROUP:-}"

# Flag to allow Kernel tests to fail.
DREVOPS_TEST_KERNEL_ALLOW_FAILURE="${DREVOPS_TEST_KERNEL_ALLOW_FAILURE:-0}"

# Kernel test group. Optional. Default runs all tests.
DREVOPS_TEST_KERNEL_GROUP="${DREVOPS_TEST_KERNEL_GROUP:-}"

# Flag to allow Functional tests to fail.
DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE="${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}"

# Functional test group. Optional. Default runs all tests.
DREVOPS_TEST_FUNCTIONAL_GROUP="${DREVOPS_TEST_FUNCTIONAL_GROUP:-}"

# Flag to allow BDD tests to fail.
DREVOPS_TEST_BDD_ALLOW_FAILURE="${DREVOPS_TEST_BDD_ALLOW_FAILURE:-0}"

# Behat profile name. Optional. Defaults to "default".
DREVOPS_TEST_BEHAT_PROFILE="${DREVOPS_TEST_BEHAT_PROFILE:-default}"

# Behat format. Optional. Defaults to "pretty".
DREVOPS_TEST_BEHAT_FORMAT="${DREVOPS_TEST_BEHAT_FORMAT:-pretty}"

# Behat tags. Optional. Default runs all tests.
DREVOPS_TEST_BEHAT_TAGS="${DREVOPS_TEST_BEHAT_TAGS:-}"

# Behat test runner index. If is set  - the value is used as a suffix for the
# parallel Behat profile name (e.g., p0, p1).
DREVOPS_TEST_BEHAT_PARALLEL_INDEX="${DREVOPS_TEST_BEHAT_PARALLEL_INDEX:-}"

# Directory to store test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# Directory to store test artifact files.
DREVOPS_TEST_ARTIFACT_DIR="${DREVOPS_TEST_ARTIFACT_DIR:-}"

# Path to the root of the project inside the container.
DREVOPS_APP=/app

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# ------------------------------------------------------------------------------

# Get test type or fallback to defaults.
DREVOPS_TEST_TYPE="${DREVOPS_TEST_TYPE:-unit-kernel-functional-bdd}"

# Create test reports and artifact directories.
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}"
[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && mkdir -p "${DREVOPS_TEST_ARTIFACT_DIR}"

[ -n "${DREVOPS_TEST_SKIP}" ] && echo "Skipping running of tests" && exit 0

if [ -z "${DREVOPS_TEST_TYPE##*unit*}" ]; then
  echo "[INFO] Running unit tests."

  phpunit_opts=(-c "${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist")
  [ -n "${DREVOPS_TEST_UNIT_GROUP}" ] && phpunit_opts+=(--group="${DREVOPS_TEST_UNIT_GROUP}")
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit.xml")

  vendor/bin/phpunit "${phpunit_opts[@]}" "${DREVOPS_WEBROOT}/modules/custom/" --exclude-group=skipped --filter '/.*Unit.*/' "$@" \
  && echo "  [OK] Unit tests passed." \
  || [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*kernel*}" ]; then
  echo "[INFO] Running Kernel tests"

  phpunit_opts=(-c "${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist")
  [ -n "${DREVOPS_TEST_KERNEL_GROUP}" ] && phpunit_opts+=(--group="${DREVOPS_TEST_KERNEL_GROUP}")
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/kernel.xml")

  vendor/bin/phpunit "${phpunit_opts[@]}" "${DREVOPS_WEBROOT}/modules/custom/" --exclude-group=skipped --filter '/.*Kernel.*/' "$@" \
  && echo "  [OK] Kernel tests passed." \
  || [ "${DREVOPS_TEST_KERNEL_ALLOW_FAILURE:-0}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*functional*}" ]; then
  echo "[INFO] Running Functional tests"

  phpunit_opts=(-c "${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist")
  [ -n "${DREVOPS_TEST_FUNCTIONAL_GROUP}" ] && phpunit_opts+=(--group="${DREVOPS_TEST_FUNCTIONAL_GROUP}")
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/functional.xml")

  vendor/bin/phpunit "${phpunit_opts[@]}" "${DREVOPS_WEBROOT}/modules/custom/" --exclude-group=skipped --filter '/.*Functional.*/' "$@" \
  && echo "  [OK] Functional tests passed." \
  || [ "${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*bdd*}" ]; then
  echo "[INFO] Running BDD tests."

  # Use parallel Behat profile if using more than a single node to run tests.
  if [ -n "${DREVOPS_TEST_BEHAT_PARALLEL_INDEX}" ] ; then
    DREVOPS_TEST_BEHAT_PROFILE="p${DREVOPS_TEST_BEHAT_PARALLEL_INDEX}"
    echo "     > Using Behat profile \"${DREVOPS_TEST_BEHAT_PROFILE}\"."
  fi

  behat_opts=(
    --strict
    --colors
    --profile="${DREVOPS_TEST_BEHAT_PROFILE}"
    --format="${DREVOPS_TEST_BEHAT_FORMAT}"
    --out std
  )

  [ -n "${DREVOPS_TEST_BEHAT_TAGS}" ] && behat_opts+=(--tags="${DREVOPS_TEST_BEHAT_TAGS}")
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && behat_opts+=(--format "junit" --out "${DREVOPS_TEST_REPORTS_DIR}/behat")
  [ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && export BEHAT_SCREENSHOT_DIR="${DREVOPS_TEST_ARTIFACT_DIR}/screenshots"

  # Run tests once and re-run on fail, but only in CI.
  vendor/bin/behat "${behat_opts[@]}" "$@" \
  || ( [ -n "${CI}" ] && vendor/bin/behat "${behat_opts[@]}" --rerun "$@" ) \
  && echo "  [OK] Behat tests passed." \
  || [ "${DREVOPS_TEST_BDD_ALLOW_FAILURE}" -eq 1 ]
fi
