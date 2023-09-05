#!/usr/bin/env bash
##
# Run unit tests.
#
# shellcheck disable=SC2015

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Path to the root of the project inside the container.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Name of the webroot directory with Drupal codebase.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# Flag to allow Unit tests to fail.
DREVOPS_TEST_UNIT_ALLOW_FAILURE="${DREVOPS_TEST_UNIT_ALLOW_FAILURE:-0}"

# Unit test group.
#
# Running Unit tests tagged with `site:unit`.
DREVOPS_TEST_UNIT_GROUP="${DREVOPS_TEST_UNIT_GROUP:-site:unit}"

# Unit test configuration file.
#
# Defaults to core's configuration file.
DREVOPS_TEST_UNIT_CONFIG="${DREVOPS_TEST_UNIT_CONFIG:-${DREVOPS_APP}/${DREVOPS_WEBROOT}/core/phpunit.xml.dist}"

# Directory to store test result files.
#
# If set, the directory is created and the JUnit formatter is used to generate
# test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Running Unit tests."

opts=()

[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}" && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit_scripts.xml")

# Run tests with set targets and skip after the first failure, but still assess the failure.
set +e

exit_code=0

# Generic tests that do not require Drupal bootstrap.
if [ "${exit_code}" -eq 0 ]; then
  info "Running Unit tests for scripts."
  vendor/bin/phpunit "${opts[@]}" "tests/phpunit" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@"
  exit_code=$?
fi

# Custom modules tests that require Drupal bootstrap.
opts=(-c "${DREVOPS_TEST_UNIT_CONFIG}")

if [ "${exit_code}" -eq 0 ]; then
  info "Running Unit tests for modules."
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit_modules.xml")
  vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/modules/custom" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@"
  exit_code=$?
fi

# Custom themes tests that require Drupal bootstrap.
if [ "${exit_code}" -eq 0 ] && [ -n "${DREVOPS_DRUPAL_THEME}" ]; then
  info "Running Unit tests for themes."
  [ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/unit_themes.xml")
  vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/themes/custom" --exclude-group=skipped --group "${DREVOPS_TEST_UNIT_GROUP}" "$@"
  exit_code=$?
fi

set -e

echo
if [ "${exit_code}" -eq 0 ]; then
  pass "Unit tests passed." && exit 0
elif [ "${DREVOPS_TEST_UNIT_ALLOW_FAILURE}" -eq 1 ]; then
  pass "Unit tests failed, but failure is allowed." && exit 0
else
  fail "Unit tests failed." && exit 1
fi
