#!/usr/bin/env bash
# shellcheck disable=SC2086
# shellcheck disable=SC2015
##
# Lint code.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow BE lint to fail.
ALLOW_BE_LINT_FAIL="${ALLOW_BE_LINT_FAIL:-0}"

# Flag to allow FE lint to fail.
ALLOW_FE_LINT_FAIL="${ALLOW_FE_LINT_FAIL:-0}"

# Comma-separated list of PHPCS targets (no spaces).
PHPCS_TARGETS="${PHPCS_TARGETS:-}"

# PHP Parallel Lint targets as a comma-separated list of extensions with no
# preceding dot or space.
PHP_LINT_TARGETS="${PHP_LINT_TARGETS:-}"

# PHP Parallel Lint extensions as a comma-separated list of extensions with
# no preceding dot or space.
PHP_LINT_EXTENSIONS="${PHP_LINT_EXTENSIONS:-php,inc,module,theme,install}"

# Drupal theme name.
DRUPAL_THEME="${DRUPAL_THEME:-}"

# ------------------------------------------------------------------------------

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
LINT_TYPE="${1:-be-fe}"

if [ -z "${LINT_TYPE##*be*}" ]; then
  # Lint code for syntax errors.
  vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${PHP_LINT_EXTENSIONS} ${PHP_LINT_TARGETS//,/ } && \
  # Lint code for coding standards.
  vendor/bin/phpcs ${PHPCS_TARGETS//,/ } || \
  # Flag to allow lint to fail.
  [ "${ALLOW_BE_LINT_FAIL}" -eq 1 ]
fi

if [ -z "${LINT_TYPE##*fe*}" ] && [ -n "${DRUPAL_THEME}" ]; then
  # Lint code using front-end linter.
  npm run --prefix "docroot/themes/custom/${DRUPAL_THEME}" lint || \
  # Flag to allow lint to fail.
  [ "${ALLOW_FE_LINT_FAIL}" -eq 1 ]
fi
