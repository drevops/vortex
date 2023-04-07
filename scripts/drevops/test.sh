#!/usr/bin/env bash
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

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip running of all tests.
# Helpful to set in CI to skip running of tests without modifying the codebase.
DREVOPS_TEST_SKIP="${DREVOPS_TEST_SKIP:-}"

# ------------------------------------------------------------------------------

# Get test type or fallback to defaults.
DREVOPS_TEST_TYPE="${DREVOPS_TEST_TYPE:-unit-kernel-functional-bdd}"

[ -n "${DREVOPS_TEST_SKIP}" ] && echo "Skipping running of tests" && exit 0

if [ -z "${DREVOPS_TEST_TYPE##*unit*}" ]; then
 ./scripts/drevops/test-unit.sh "$@"
fi

if [ -z "${DREVOPS_TEST_TYPE##*kernel*}" ]; then
  ./scripts/drevops/test-kernel.sh "$@"
fi

if [ -z "${DREVOPS_TEST_TYPE##*functional*}" ]; then
  ./scripts/drevops/test-functional.sh "$@"
fi

if [ -z "${DREVOPS_TEST_TYPE##*bdd*}" ]; then
  ./scripts/drevops/test-bdd.sh "$@"
fi
