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

# Flag to allow BE lint to fail.
DREVOPS_LINT_BE_ALLOW_FAILURE="${DREVOPS_LINT_BE_ALLOW_FAILURE:-0}"

# Flag to allow FE lint to fail.
DREVOPS_LINT_FE_ALLOW_FAILURE="${DREVOPS_LINT_FE_ALLOW_FAILURE:-0}"

# Comma-separated list of PHPCS targets (no spaces).
DREVOPS_LINT_PHPCS_TARGETS="${DREVOPS_LINT_PHPCS_TARGETS:-}"

# PHP Parallel Lint targets as a comma-separated list of extensions with no
# preceding dot or space.
DREVOPS_LINT_PHPLINT_TARGETS="${DREVOPS_LINT_PHPLINT_TARGETS:-}"

# PHP Parallel Lint extensions as a comma-separated list of extensions with
# no preceding dot or space.
DREVOPS_LINT_PHPLINT_EXTENSIONS="${DREVOPS_LINT_PHPLINT_EXTENSIONS:-php,inc,module,theme,install}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# ------------------------------------------------------------------------------
echo "INFO Linting code."

[ -n "${DREVOPS_LINT_SKIP}" ] && echo "  OK Skipping code linting" && exit 0

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
DREVOPS_LINT_TYPE="${1:-be-fe}"

if [ -z "${DREVOPS_LINT_TYPE##*be*}" ]; then
  vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${DREVOPS_LINT_PHPLINT_EXTENSIONS// /} ${DREVOPS_LINT_PHPLINT_TARGETS//,/ } \
  && vendor/bin/phpcs ${DREVOPS_LINT_PHPCS_TARGETS//,/ } \
  && echo "  OK Back-end code linted successfully." \
  || [ "${DREVOPS_LINT_BE_ALLOW_FAILURE}" -eq 1 ]
fi

if [ -z "${DREVOPS_LINT_TYPE##*fe*}" ] && [ -n "${DREVOPS_DRUPAL_THEME}" ] && grep -q lint "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}/package.json"; then
  # Lint code using front-end linter.
  npm run --prefix "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}" lint \
  && echo "  OK Front-end code linted successfully." \
  || [ "${DREVOPS_LINT_FE_ALLOW_FAILURE}" -eq 1 ]
fi
