#!/usr/bin/env bash
##
# Lint BE code.
#
# shellcheck disable=SC2086
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow BE lint to fail.
DREVOPS_LINT_BE_ALLOW_FAILURE="${DREVOPS_LINT_BE_ALLOW_FAILURE:-0}"

# Comma-separated list of PHPCS targets (no spaces).
DREVOPS_LINT_PHPCS_TARGETS="${DREVOPS_LINT_PHPCS_TARGETS:-}"

# PHP Parallel Lint targets as a comma-separated list of extensions with no
# preceding dot or space.
DREVOPS_LINT_PHPLINT_TARGETS="${DREVOPS_LINT_PHPLINT_TARGETS:-}"

# PHP Parallel Lint extensions as a comma-separated list of extensions with
# no preceding dot or space.
DREVOPS_LINT_PHPLINT_EXTENSIONS="${DREVOPS_LINT_PHPLINT_EXTENSIONS:-php,inc,module,theme,install}"

# ------------------------------------------------------------------------------

vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e ${DREVOPS_LINT_PHPLINT_EXTENSIONS// /} ${DREVOPS_LINT_PHPLINT_TARGETS//,/ } &&
  vendor/bin/phpcs ${DREVOPS_LINT_PHPCS_TARGETS//,/ } &&
  echo "  [OK] Back-end code linted successfully." ||
  [ "${DREVOPS_LINT_BE_ALLOW_FAILURE}" -eq 1 ]
