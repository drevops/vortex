#!/usr/bin/env bash
# shellcheck disable=SC2086
# shellcheck disable=SC2015
##
# Run tests.
#

set -e

# Flag to allow Unit tests to fail.
ALLOW_UNIT_TESTS_FAIL="${ALLOW_UNIT_TESTS_FAIL:-0}"

# Flag to allow Kernel tests to fail.
ALLOW_KERNEL_TESTS_FAIL="${ALLOW_KERNEL_TESTS_FAIL:-0}"

# Flag to allow Functional tests to fail.
ALLOW_FUNCTIONAL_TESTS_FAIL="${ALLOW_FUNCTIONAL_TESTS_FAIL:-0}"

# Flag to allow BDD tests to fail.
ALLOW_BDD_TESTS_FAIL="${ALLOW_BDD_TESTS_FAIL:-0}"

# Behat profile name.
BEHAT_PROFILE="${BEHAT_PROFILE:-}"

# ------------------------------------------------------------------------------

# Provide one of the arguments (unit, kernel, functional, bdd) to run selected
# tests.
# If no argument is provided, all tests will be ran.
TEST_TYPE="${1:-unit-kernel-functional-bdd}"

if [ -z "${TEST_TYPE##*unit*}" ]; then
  shift
  vendor/bin/phpunit -c /app/docroot/core/phpunit.xml.dist docroot/modules/custom/ --filter '/.*Unit.*/' "$@" \
  || [ "${ALLOW_UNIT_TESTS_FAIL}" -eq 1 ]
fi

if [ -z "${TEST_TYPE##*kernel*}" ]; then
  shift
  vendor/bin/phpunit -c /app/docroot/core/phpunit.xml.dist docroot/modules/custom/ --filter '/.*Kernel.*/' "$@" \
  || [ "${ALLOW_KERNEL_TESTS_FAIL:-0}" -eq 1 ]
fi

if [ -z "${TEST_TYPE##*functional*}" ]; then
  shift
  vendor/bin/phpunit -c /app/docroot/core/phpunit.xml.dist docroot/modules/custom/ --filter '/.*Functional.*/' "$@" \
  || [ "${ALLOW_FUNCTIONAL_TESTS_FAIL:-0}" -eq 1 ]
fi

if [ -z "${TEST_TYPE##*bdd*}" ]; then
  shift
  [ -n "${BEHAT_PROFILE}" ] && BEHAT_PROFILE=--profile=${BEHAT_PROFILE} || true
  vendor/bin/behat --strict --colors ${BEHAT_PROFILE} "$@" || \
  [ "${ALLOW_BDD_TESTS_FAIL}" -eq 1 ]
fi
