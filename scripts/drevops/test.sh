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
# TEST_TYPE=bdd ./test.sh
#
# Run bdd tests:
# TEST_TYPE=unit ./test.sh

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow Unit tests to fail.
ALLOW_UNIT_TESTS_FAIL="${ALLOW_UNIT_TESTS_FAIL:-0}"

# Flag to allow BDD tests to fail.
ALLOW_BDD_TESTS_FAIL="${ALLOW_BDD_TESTS_FAIL:-0}"

# Directory to store test result files.
TEST_LOG_DIR="${TEST_LOG_DIR:-}"

# Directory to store test artifact files.
TEST_ARTIFACT_DIR="${TEST_ARTIFACT_DIR:-$(pwd)}"

# Behat profile name. Optional. Defaults to "default".
BEHAT_PROFILE="${BEHAT_PROFILE:-default}"

# Behat format. Optional. Defaults to "pretty".
BEHAT_FORMAT="${BEHAT_FORMAT:-pretty}"

# ------------------------------------------------------------------------------

# Get test type or fallback to defaults.
TEST_TYPE="${TEST_TYPE:-unit-bdd}"

# Create log and artifact directories.
[ -n "${TEST_LOG_DIR}" ] && mkdir -p "${TEST_LOG_DIR}"
[ -n "${TEST_ARTIFACT_DIR}" ] && mkdir -p "${TEST_ARTIFACT_DIR}"

if [ -z "${TEST_TYPE##*unit*}" ]; then
  echo "==> Run unit tests"

  phpunit_opts=(-c /app/phpunit.xml)
  [ -n "${TEST_LOG_DIR}" ] && phpunit_opts+=(--log-junit "${TEST_LOG_DIR}"/phpunit.xml)

  vendor/bin/phpunit "${phpunit_opts[@]}" "$@" || [ "${ALLOW_UNIT_TESTS_FAIL}" -eq 1 ]
fi

if [ -z "${TEST_TYPE##*bdd*}" ]; then
  echo "==> Run BDD tests"

  # Use parallel Behat profile if using more than a single node to run tests.
  # @todo: Add support for other CI providers.
  # @todo:d Fix this.
  # set -x
  if [ "${CIRCLE_NODE_TOTAL:-1}" -gt "1" ] ; then
    BEHAT_PROFILE="p${CIRCLE_NODE_INDEX}"
    BEHAT_FORMAT="progress_fail"
  fi
  set +x
  [ -n "${TEST_ARTIFACT_DIR}" ] && export BEHAT_SCREENSHOT_DIR="${TEST_ARTIFACT_DIR}/screenshots"

  vendor/bin/behat --strict --colors --profile="${BEHAT_PROFILE}" --format="${BEHAT_FORMAT}" "$@" || [ "${ALLOW_BDD_TESTS_FAIL}" -eq 1 ]
fi
