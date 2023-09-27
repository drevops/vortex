#!/usr/bin/env bash
##
# Run functional tests.
#
# shellcheck disable=SC2015

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Name of the webroot directory with Drupal codebase.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Flag to allow Functional tests to fail.
DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE="${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE:-0}"

# Functional test group.
#
# Running Functional tests tagged with `site:functional`.
DREVOPS_TEST_FUNCTIONAL_GROUP="${DREVOPS_TEST_FUNCTIONAL_GROUP:-site:functional}"

# Functional test configuration file.
#
# Defaults to core's configuration file.
DREVOPS_TEST_FUNCTIONAL_CONFIG="${DREVOPS_TEST_FUNCTIONAL_CONFIG:-./${DREVOPS_WEBROOT}/core/phpunit.xml.dist}"

# Directory to store test result files.
#
# If set, the directory is created and the JUnit formatter is used to generate
# test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Running Functional tests."

opts=(-c "${DREVOPS_TEST_FUNCTIONAL_CONFIG/.\//\/app/}")

[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}" && opts+=(--log-junit "${DREVOPS_TEST_REPORTS_DIR}/phpunit/functional_modules.xml")

# Run tests with set targets and skip after the first failure, but still assess the failure.
set +e

exit_code=0

if [ "${exit_code}" -eq 0 ]; then
  info "Running Functional tests for modules."
  vendor/bin/phpunit "${opts[@]}" "${DREVOPS_WEBROOT}/modules/custom/" --exclude-group=skipped --group "${DREVOPS_TEST_FUNCTIONAL_GROUP}" "$@"
  exit_code=$?
fi

set -e

echo
if [ "${exit_code}" -eq 0 ]; then
  pass "Functional tests passed." && exit 0
elif [ "${DREVOPS_TEST_FUNCTIONAL_ALLOW_FAILURE}" -eq 1 ]; then
  pass "Functional tests failed, but failure is allowed." && exit 0
else
  fail "Functional tests failed." && exit 1
fi
