#!/usr/bin/env bash
##
# Lint BE code.
#
# shellcheck disable=SC2086
# shellcheck disable=SC2015

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Flag to allow BE lint to fail.
DREVOPS_LINT_BE_ALLOW_FAILURE="${DREVOPS_LINT_BE_ALLOW_FAILURE:-0}"

# PHP Parallel Lint comma-separated list of targets.
DREVOPS_LINT_PHPLINT_TARGETS="${DREVOPS_LINT_PHPLINT_TARGETS:-}"

# PHP Parallel Lint comma-separated list of extensions (no preceding dot).
DREVOPS_LINT_PHPLINT_EXTENSIONS="${DREVOPS_LINT_PHPLINT_EXTENSIONS:-php,inc,module,theme,install}"

# PHPCS comma-separated list of targets.
DREVOPS_LINT_PHPCS_TARGETS="${DREVOPS_LINT_PHPCS_TARGETS:-}"

# PHPMD comma-separated list of rules.
DREVOPS_LINT_PHPMD_RULESETS="${DREVOPS_LINT_PHPMD_RULESETS:-}"

# PHPMD comma-separated list of targets.
DREVOPS_LINT_PHPMD_TARGETS="${DREVOPS_LINT_PHPMD_TARGETS:-}"

# PHPStan comma-separated list of targets.
DREVOPS_LINT_PHPSTAN_TARGETS="${DREVOPS_LINT_PHPSTAN_TARGETS:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${DREVOPS_LINT_PHPLINT_EXTENSIONS// /} ${DREVOPS_LINT_PHPLINT_TARGETS//,/ } &&
  vendor/bin/phpcs ${DREVOPS_LINT_PHPCS_TARGETS//,/ } &&
  vendor/bin/phpmd --exclude vendor,node_modules ${DREVOPS_LINT_PHPMD_TARGETS//, /,} text "${DREVOPS_LINT_PHPMD_RULESETS//, /,}" &&
  vendor/bin/phpstan analyse ${DREVOPS_LINT_PHPSTAN_TARGETS//, / } &&
  pass "Back-end code has passed the linter check." ||
  [ "${DREVOPS_LINT_BE_ALLOW_FAILURE}" -eq 1 ]
