#!/usr/bin/env bash
##
# Build and publish the Storybook component library.
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

# Public files directory relative to the webroot.
DRUPAL_PUBLIC_FILES="${DRUPAL_PUBLIC_FILES:-sites/default/files}"

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

theme_dir="./${WEBROOT}/themes/custom/${DRUPAL_THEME}"
app_dir="./${WEBROOT}/${DRUPAL_PUBLIC_FILES}/storybook"

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

task "Installing Storybook module."
drush pm:install storybook
pass "Installed Storybook module."

task "Generating stories."
drush storybook:generate-all-stories --omit-server-url
pass "Generated stories."

if [ ! -x "${theme_dir}/node_modules/.bin/storybook" ]; then
  note "Skipped building the Storybook application: theme dependencies are not installed."
  info "Finished Storybook operations."
  exit 0
fi

task "Building the Storybook application."
npm --prefix="${theme_dir}" run storybook-build
pass "Built the Storybook application."

task "Publishing the Storybook application."
rm -rf "${app_dir}"
mkdir -p "$(dirname "${app_dir}")"
mv "${theme_dir}/storybook-static" "${app_dir}"
pass "Published the Storybook application."

info "Finished Storybook operations."
