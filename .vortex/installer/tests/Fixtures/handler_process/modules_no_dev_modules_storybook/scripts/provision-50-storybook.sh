#!/usr/bin/env bash
##
# Generate the Storybook stories and build the static component library.
#
# This script is called during site provisioning via the provision script.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Skip Storybook operations.
DRUPAL_STORYBOOK_SKIP="${DRUPAL_STORYBOOK_SKIP:-0}"

# Name of the webroot directory with Drupal codebase.
WEBROOT="${WEBROOT:-web}"

# Drupal theme name.
DRUPAL_THEME="${DRUPAL_THEME:-}"

# Directory with the Storybook configuration and its npm scripts.
VORTEX_STORYBOOK_DIR="${VORTEX_STORYBOOK_DIR:-./${WEBROOT}/themes/custom/${DRUPAL_THEME}}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     + %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started Storybook operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

note "Storybook skip: ${DRUPAL_STORYBOOK_SKIP}"
echo

if [ "${DRUPAL_STORYBOOK_SKIP}" = "1" ]; then
  info "Skipped Storybook operations. DRUPAL_STORYBOOK_SKIP is set to 1."
  exit 0
fi

# The story render route is open only in the environments where
# settings.storybook.php loads the development services.
if ! echo "${environment}" | grep -qxF -e local -e ci -e dev; then
  note "Skipped Storybook operations in non-development environment."
  info "Finished Storybook operations."
  exit 0
fi

if [ ! -x "${VORTEX_STORYBOOK_DIR}/node_modules/.bin/storybook" ]; then
  note "Skipped building the Storybook application: theme dependencies are not installed."
  info "Finished Storybook operations."
  exit 0
fi

./vendor/bin/vortex-storybook build

info "Finished Storybook operations."
