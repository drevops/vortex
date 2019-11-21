#!/usr/bin/env bash
# shellcheck disable=SC2086
# shellcheck disable=SC2015
##
# Lint code.
#

set -e

# Flag to allow lint to fail.
ALLOW_LINT_FAIL="${ALLOW_LINT_FAIL:-0}"

# Comma-separated list of PHPCS targets (no spaces).
PHPCS_TARGETS="${PHPCS_TARGETS:-docroot/profiles/custom/your_site_profile,docroot/modules/custom,docroot/themes/custom,docroot/sites/default/settings.php,tests}"

# PHP Parallel Lint extensions as a comma-separated list of extensions with
# no preceding dot or space.
PHP_LINT_EXTENSIONS="${PHP_LINT_EXTENSIONS:-php,inc,module,theme,install}"

# PHP Parallel Lint targets as a comma-separated list of extensions with no
# preceding dot or space.
PHP_LINT_TARGETS="${PHP_LINT_TARGETS:-tests,docroot/profiles/custom/your_site_profile,docroot/modules/custom,docroot/themes/custom,docroot/sites/default/settings.php}"

# ------------------------------------------------------------------------------

# Provide argument as 'be' or 'fe' to lint only back-end or front-end code.
# If no argument is provided, all code will be linted.
LINT_TYPE="${1:-befe}"

if [ -z "${LINT_TYPE##*be*}" ]; then
  # Lint code for syntax errors.
  vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${PHP_LINT_EXTENSIONS} ${PHP_LINT_TARGETS//,/ } && \
  # Lint code for coding standards.
  vendor/bin/phpcs -v ${PHPCS_TARGETS//,/ } || \
  # Flag to allow lint to fail.
  [ "${ALLOW_LINT_FAIL}" -eq 1 ]
fi

if [ -z "${LINT_TYPE##*fe*}" ]; then
  # Lint code using front-end linter.
  npm run lint || \
  # Flag to allow lint to fail.
  [ "${ALLOW_LINT_FAIL}" -eq 1 ]
fi
