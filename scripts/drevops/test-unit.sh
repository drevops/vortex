#!/usr/bin/env bash
##
# Run unit tests.
#
# Usage:
# ./test-unit.sh
#
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the root of the project inside the container.
DREVOPS_APP=/app

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# Flag to allow Unit tests to fail.
DREVOPS_TEST_UNIT_ALLOW_FAILURE="${DREVOPS_TEST_UNIT_ALLOW_FAILURE:-0}"

# Unit test group. Optional. Defaults to running Unit tests tagged with `site:unit`.
DREVOPS_TEST_UNIT_GROUP="${DREVOPS_TEST_UNIT_GROUP:-site:unit}"

# Unit test configuration file. Optional. Defaults to core's configuration.
DREVOPS_TEST_UNIT_CONFIG="${DREVOPS_TEST_UNIT_CONFIG:-${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist}"

# Directory to store test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# Directory to store test artifact files.
DREVOPS_TEST_ARTIFACT_DIR="${DREVOPS_TEST_ARTIFACT_DIR:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m  [OK] %s\033[0m\n" "$1" || printf "  [OK] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Running unit tests."

# Create test reports and artifact directories.
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}"
[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && mkdir -p "${DREVOPS_TEST_ARTIFACT_DIR}"

# Generic tests that do not require Drupal bootstrap.
opts=()

[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit.xml")

vendor/bin/phpunit "${opts[@]}" "tests/phpunit" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@" &&
  pass "Unit tests for scripts passed." ||
  [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]

# Custom modules tests that require Drupal bootstrap.
opts=(-c "${DREVOPS_TEST_UNIT_CONFIG}")

[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit_modules.xml")

vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/modules/custom" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@" &&
  pass "Unit tests for modules passed." ||
  [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]

# Custom theme tests that require Drupal bootstrap.
if [ -n "${DREVOPS_DRUPAL_THEME}" ]; then
  opts=(-c "${DREVOPS_TEST_UNIT_CONFIG}")

  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit_themes.xml")

  vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/themes/custom" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@" &&
    pass "Unit tests for themes passed." ||
    [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]
fi
