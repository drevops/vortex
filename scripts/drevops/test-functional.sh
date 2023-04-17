#!/usr/bin/env bash
##
# Run tests.
#
# Usage:
# ./test-functional.sh
#
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the root of the project inside the container.
DREVOPS_APP=/app

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Flag to allow Functional tests to fail.
DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE="${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}"

# Kernel test group. Optional. Defaults to running Functional tests tagged with `site:functional`.
DREVOPS_TEST_FUNCTIONAL_GROUP="${DREVOPS_TEST_FUNCTIONAL_GROUP:-site:functional}"

# Functional test configuration file. Optional. Defaults to core's configuration.
DREVOPS_TEST_FUNCTIONAL_CONFIG="${DREVOPS_TEST_FUNCTIONAL_CONFIG:-${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist}"

# Directory to store test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# Directory to store test artifact files.
DREVOPS_TEST_ARTIFACT_DIR="${DREVOPS_TEST_ARTIFACT_DIR:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Running Functional tests"

# Create test reports and artifact directories.
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}"
[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && mkdir -p "${DREVOPS_TEST_ARTIFACT_DIR}"

opts=(-c "${DREVOPS_TEST_FUNCTIONAL_CONFIG}")

[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/functional.xml")

vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/modules/custom/" --exclude-group=skipped --group "${DREVOPS_TEST_FUNCTIONAL_GROUP}" "$@" &&
  pass "Functional tests passed." ||
  [ "${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}" -eq 1 ]
