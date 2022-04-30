#!/usr/bin/env bash
# shellcheck disable=SC2086
# shellcheck disable=SC2015
##
# Lint code.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow BE lint to fail.
DREVOPS_ALLOW_BE_LINT_FAIL="${DREVOPS_ALLOW_BE_LINT_FAIL:-0}"

# Flag to allow FE lint to fail.
DREVOPS_ALLOW_FE_LINT_FAIL="${DREVOPS_ALLOW_FE_LINT_FAIL:-0}"

# Comma-separated list of PHPCS targets (no spaces).
DREVOPS_PHPCS_TARGETS="${DREVOPS_PHPCS_TARGETS:-}"

# PHP Parallel Lint targets as a comma-separated list of extensions with no
# preceding dot or space.
DREVOPS_PHPLINT_TARGETS="${DREVOPS_PHPLINT_TARGETS:-}"

# PHP Parallel Lint extensions as a comma-separated list of extensions with
# no preceding dot or space.
DREVOPS_PHPLINT_EXTENSIONS="${DREVOPS_PHPLINT_EXTENSIONS:-php,inc,module,theme,install}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# ------------------------------------------------------------------------------

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
LINT_TYPE="${1:-be-fe}"

if [ -z "${LINT_TYPE##*be*}" ]; then
  # Lint code for syntax errors.
  vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${DREVOPS_PHPLINT_EXTENSIONS} ${DREVOPS_PHPLINT_TARGETS//,/ } && \
  # Lint code for coding standards.
  vendor/bin/phpcs ${DREVOPS_PHPCS_TARGETS//,/ } || \
  # Flag to allow lint to fail.
  [ "${DREVOPS_ALLOW_BE_LINT_FAIL}" -eq 1 ]
fi

if [ -z "${LINT_TYPE##*fe*}" ] && [ -n "${DREVOPS_DRUPAL_THEME}" ] && grep -q lint "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}/package.json"; then
  # Lint code using front-end linter.
  npm run --prefix "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}" lint || \
  # Flag to allow lint to fail.
  [ "${DREVOPS_ALLOW_FE_LINT_FAIL}" -eq 1 ]
fi
