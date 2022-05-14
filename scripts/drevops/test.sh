#!/usr/bin/env bash
# shellcheck disable=SC2086
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

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow Unit tests to fail.
DREVOPS_TEST_UNIT_ALLOW_FAILURE="${DREVOPS_TEST_UNIT_ALLOW_FAILURE:-0}"

# Flag to allow Kernel tests to fail.
DREVOPS_TEST_KERNEL_ALLOW_FAILURE="${DREVOPS_TEST_KERNEL_ALLOW_FAILURE:-0}"

# Flag to allow Functional tests to fail.
DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE="${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}"

# Flag to allow BDD tests to fail.
DREVOPS_TEST_BDD_ALLOW_FAILURE="${DREVOPS_TEST_BDD_ALLOW_FAILURE:-0}"

# Directory to store test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# Directory to store test artifact files.
DREVOPS_TEST_ARTIFACT_DIR="${DREVOPS_TEST_ARTIFACT_DIR:-}"

# Behat profile name. Optional. Defaults to "default".
DREVOPS_TEST_BEHAT_PROFILE="${DREVOPS_TEST_BEHAT_PROFILE:-default}"

# Behat format. Optional. Defaults to "pretty".
DREVOPS_TEST_BEHAT_FORMAT="${DREVOPS_TEST_BEHAT_FORMAT:-pretty}"

# Behat test runner index. If is set  - the value is used as a suffix for the
# parallel Behat profile name (e.g., p0, p1).
DREVOPS_TEST_BEHAT_PARALLEL_INDEX="${DREVOPS_TEST_BEHAT_PARALLEL_INDEX:-}"

# ------------------------------------------------------------------------------

# Get test type or fallback to defaults.
DREVOPS_TEST_TYPE="${DREVOPS_TEST_TYPE:-unit-kernel-functional-bdd}"

# Create test reports and artifact directories.
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}"
[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && mkdir -p "${DREVOPS_TEST_ARTIFACT_DIR}"

if [ -z "${DREVOPS_TEST_TYPE##*unit*}" ]; then
  echo "==> Run unit tests."

  phpunit_opts=(-c /app/docroot/core/phpunit.xml.dist)
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}"/phpunit/unit.xml)

  vendor/bin/phpunit "${phpunit_opts[@]}" docroot/modules/custom/ --filter '/.*Unit.*/' "$@" \
  || [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*kernel*}" ]; then
  echo "==> Run Kernel tests"

  phpunit_opts=(-c /app/docroot/core/phpunit.xml.dist)
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}"/phpunit/kernel.xml)

  vendor/bin/phpunit "${phpunit_opts[@]}" docroot/modules/custom/ --filter '/.*Kernel.*/' "$@" \
  || [ "${DREVOPS_TEST_KERNEL_ALLOW_FAILURE:-0}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*functional*}" ]; then
  echo "==> Run Functional tests"

  phpunit_opts=(-c /app/docroot/core/phpunit.xml.dist)
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && phpunit_opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}"/phpunit/functional.xml)

  vendor/bin/phpunit "${phpunit_opts[@]}" docroot/modules/custom/ --filter '/.*Functional.*/' "$@" \
  || [ "${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}" -eq 1 ]
fi

if [ -z "${DREVOPS_TEST_TYPE##*bdd*}" ]; then
  echo "==> Run BDD tests."

  # Use parallel Behat profile if using more than a single node to run tests.
  if [ -n "${DREVOPS_TEST_BEHAT_PARALLEL_INDEX}" ] ; then
    DREVOPS_TEST_BEHAT_PROFILE="p${DREVOPS_TEST_BEHAT_PARALLEL_INDEX}"
    echo "==> Running using profile \"${DREVOPS_TEST_BEHAT_PROFILE}\"."
  fi

  [ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && export BEHAT_SCREENSHOT_DIR="${DREVOPS_TEST_ARTIFACT_DIR}/screenshots"

  behat_opts=(
    --strict
    --colors
    --profile="${DREVOPS_TEST_BEHAT_PROFILE}"
    --format="${DREVOPS_TEST_BEHAT_FORMAT}"
    --out std
  )

  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && behat_opts+=(--format "junit" --out "${DREVOPS_TEST_REPORTS_DIR}"/behat)

  vendor/bin/behat "${behat_opts[@]}" "$@" \
  || ( [ -n "${CI}" ] && vendor/bin/behat "${behat_opts[@]}" "$@" ) \
  || [ "${DREVOPS_TEST_BDD_ALLOW_FAILURE}" -eq 1 ]
fi
