#!/usr/bin/env bash
##
# Lint FE code.
#
# shellcheck disable=SC2086,SC2015,SC2317

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Flag to allow FE lint to fail.
DREVOPS_LINT_FE_ALLOW_FAILURE="${DREVOPS_LINT_FE_ALLOW_FAILURE:-0}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# Name of the webroot directory with Drupal codebase.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Running front-end code linter checks."

# Run tools and skip after the first failure, but still assess the failure.
set +e

exit_code=0

if [ "${exit_code}" -eq 0 ] && [ -n "${DREVOPS_DRUPAL_THEME}" ] && grep -q lint "${DREVOPS_WEBROOT}/themes/custom/${DREVOPS_DRUPAL_THEME}/package.json"; then
  info "Running theme npm lint."
  npm run --prefix "${DREVOPS_WEBROOT}/themes/custom/${DREVOPS_DRUPAL_THEME}" lint
  exit_code=$?
fi

if [ "${exit_code}" -eq 0 ] && [ -n "${DREVOPS_LINT_TWIGCS_TARGETS:-}" ]; then
  info "Running Twigcs."
  # Twigcs expects all targets to exist, so we need to filter out non-existing ones.
  oldifs="$IFS"
  IFS=', '
  set -- ${DREVOPS_LINT_TWIGCS_TARGETS}
  IFS="${oldifs}"
  twigcs_valid_targets=""
  for target in "$@"; do
    for dir in $target; do
      [ -d "$dir" ] && twigcs_valid_targets="${twigcs_valid_targets:+$twigcs_valid_targets, }${dir}"
    done
  done

  if [ -n "${twigcs_valid_targets}" ]; then
    vendor/bin/twigcs ${twigcs_valid_targets//, / }
    exit_code=$?
  fi
fi

set -e

echo
if [ "${exit_code}" -eq 0 ]; then
  pass "Front-end code passed the linter checks." && exit 0
elif [ "${DREVOPS_LINT_FE_ALLOW_FAILURE}" -eq 1 ]; then
  pass "Front-end code failed the linter checks, but failure is allowed." && exit 0
else
  fail "Front-end code failed the linter checks." && exit 1
fi
