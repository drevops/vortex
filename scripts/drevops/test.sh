#!/usr/bin/env bash
##
# Run tests.
#
# This is a router script to call relevant scripts based on type.
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

# Test types to run. Can be a combination of comma-separated values:
# unit,kernel,functional,bdd
DREVOPS_TEST_TYPE="${DREVOPS_TEST_TYPE:-unit,kernel,functional,bdd}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m  [OK] %s\033[0m\n" "$1" || printf "  [OK] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

[ -n "${DREVOPS_TEST_SKIP}" ] && note "Skipping running of tests" && exit 0

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
