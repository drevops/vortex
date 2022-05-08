#!/usr/bin/env bash
# shellcheck disable=SC2086
# shellcheck disable=SC2015
##
# Lint code.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

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

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
DREVOPS_LINT_TYPE="${1:-be-fe}"

if [ -z "${DREVOPS_LINT_TYPE##*be*}" ]; then
  # Lint code for syntax errors.
  vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${DREVOPS_LINT_PHPLINT_EXTENSIONS// /} ${DREVOPS_LINT_PHPLINT_TARGETS//,/ } && \
  # Lint code for coding standards.
  vendor/bin/phpcs ${DREVOPS_LINT_PHPCS_TARGETS//,/ } || \
  # Flag to allow lint to fail.
  [ "${DREVOPS_LINT_BE_ALLOW_FAILURE}" -eq 1 ]
fi

if [ -z "${DREVOPS_LINT_TYPE##*fe*}" ] && [ -n "${DREVOPS_DRUPAL_THEME}" ] && grep -q lint "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}/package.json"; then
  # Lint code using front-end linter.
  npm run --prefix "docroot/themes/custom/${DREVOPS_DRUPAL_THEME}" lint || \
  # Flag to allow lint to fail.
  [ "${DREVOPS_LINT_FE_ALLOW_FAILURE}" -eq 1 ]
fi
