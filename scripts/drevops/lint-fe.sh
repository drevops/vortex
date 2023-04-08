#!/usr/bin/env bash
##
# Lint FE code.
#
# shellcheck disable=SC2086
# shellcheck disable=SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Flag to allow FE lint to fail.
DREVOPS_LINT_FE_ALLOW_FAILURE="${DREVOPS_LINT_FE_ALLOW_FAILURE:-0}"

# Drupal theme name.
DREVOPS_DRUPAL_THEME="${DREVOPS_DRUPAL_THEME:-}"

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# ------------------------------------------------------------------------------

if [ -n "${DREVOPS_DRUPAL_THEME}" ] && grep -q lint "${DREVOPS_WEBROOT}/themes/custom/${DREVOPS_DRUPAL_THEME}/package.json"; then
  # Lint code using front-end linter.
  npm run --prefix "${DREVOPS_WEBROOT}/themes/custom/${DREVOPS_DRUPAL_THEME}" lint \
  && echo "  [OK] Front-end code linted successfully." \
  || [ "${DREVOPS_LINT_FE_ALLOW_FAILURE}" -eq 1 ]
fi
