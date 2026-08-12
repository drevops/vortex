#!/usr/bin/env bash
##
# Enable development modules.
#
# This script is called during site provisioning via the provision script.
#
# Development modules are enabled here rather than in the example script so that
# they remain available after the example script is adjusted or removed.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     < %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started development modules operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

if ! echo "${environment}" | grep -qxF -e local -e ci -e dev -e stage; then
  note "Skipped installing development modules in production environment."
  exit 0
fi

#;< MODULE_SDC_DEVEL
task "Installing Single Directory Component development tools."
drush pm:install sdc_devel || true
pass "Installed Single Directory Component development tools."
#;> MODULE_SDC_DEVEL

#;< MODULE_DEVEL
task "Installing Devel module."
drush pm:install devel || true
pass "Installed Devel module."
#;> MODULE_DEVEL

info "Finished development modules operations."
