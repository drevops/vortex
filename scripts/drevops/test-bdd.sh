#!/usr/bin/env bash
##
# Run tests.
#
# Usage:
# ./test-bdd.sh
#
# shellcheck disable=SC2015

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Flag to allow BDD tests to fail.
DREVOPS_TEST_BDD_ALLOW_FAILURE="${DREVOPS_TEST_BDD_ALLOW_FAILURE:-0}"

# Behat profile name. Optional. Defaults to "default".
DREVOPS_TEST_BEHAT_PROFILE="${DREVOPS_TEST_BEHAT_PROFILE:-default}"

# Behat format. Optional. Defaults to "pretty".
DREVOPS_TEST_BEHAT_FORMAT="${DREVOPS_TEST_BEHAT_FORMAT:-pretty}"

# Behat tags. Optional. Default runs all tests.
DREVOPS_TEST_BEHAT_TAGS="${DREVOPS_TEST_BEHAT_TAGS:-}"

# Test runner parallel index.
# If is set, the value is used as a suffix for the Behat profile name (e.g. p0, p1).
DREVOPS_TEST_PARALLEL_INDEX="${DREVOPS_TEST_PARALLEL_INDEX:-}"

# Directory to store test result files.
DREVOPS_TEST_REPORTS_DIR="${DREVOPS_TEST_REPORTS_DIR:-}"

# Directory to store test artifact files.
DREVOPS_TEST_ARTIFACT_DIR="${DREVOPS_TEST_ARTIFACT_DIR:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Running BDD tests."

# Create test reports and artifact directories.
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && mkdir -p "${DREVOPS_TEST_REPORTS_DIR}"
[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && mkdir -p "${DREVOPS_TEST_ARTIFACT_DIR}"

# Use parallel Behat profile if using more than a single node to run tests.
if [ -n "${DREVOPS_TEST_PARALLEL_INDEX}" ]; then
  DREVOPS_TEST_BEHAT_PROFILE="p${DREVOPS_TEST_PARALLEL_INDEX}"
  note "Using Behat profile \"${DREVOPS_TEST_BEHAT_PROFILE}\"."
fi

opts=(
  --strict
  --colors
  --profile="${DREVOPS_TEST_BEHAT_PROFILE}"
  --format="${DREVOPS_TEST_BEHAT_FORMAT}"
  --out std
)

[ -n "${DREVOPS_TEST_ARTIFACT_DIR}" ] && export BEHAT_SCREENSHOT_DIR="${DREVOPS_TEST_ARTIFACT_DIR}/screenshots"
[ -n "${DREVOPS_TEST_REPORTS_DIR}" ] && opts+=(--format "junit" --out "${DREVOPS_TEST_REPORTS_DIR}/behat")

[ -n "${DREVOPS_TEST_BEHAT_TAGS}" ] && opts+=(--tags="${DREVOPS_TEST_BEHAT_TAGS}")

# Run tests once and re-run on fail, but only in CI.
vendor/bin/behat "${opts[@]}" "$@" ||
  ([ -n "${CI}" ] && vendor/bin/behat "${opts[@]}" --rerun "$@") &&
  pass "Behat tests passed." ||
  [ "${DREVOPS_TEST_BDD_ALLOW_FAILURE}" -eq 1 ]
