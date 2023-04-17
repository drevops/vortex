#!/usr/bin/env bash
##
# Lint code.
#
# shellcheck disable=SC2086
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to skip code linting.
# Helpful to set in CI to code linting without modifying the codebase.
DREVOPS_LINT_SKIP="${DREVOPS_LINT_SKIP:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Linting code."

[ -n "${DREVOPS_LINT_SKIP}" ] && pass "Skipping code linting" && exit 0

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
DREVOPS_LINT_TYPE="${1:-be-fe}"

if [ -z "${DREVOPS_LINT_TYPE##*be*}" ]; then
 ./scripts/drevops/lint-be.sh "$@"
fi

if [ -z "${DREVOPS_LINT_TYPE##*fe*}" ]; then
 ./scripts/drevops/lint-fe.sh "$@"
fi
