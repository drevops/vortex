#!/usr/bin/env bash
##
# Enable development modules and generate content.
#
# This script is called during site provisioning via the provision script.
#
# Development modules are enabled here rather than in the example script so that
# they remain available after the example script is adjusted or removed.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

#;< MODULE_GENERATED_CONTENT
# Skip content generation.
DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"
#;> MODULE_GENERATED_CONTENT

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

#;< MODULE_GENERATED_CONTENT
if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
  note "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
else
  # The module creates content from its own hook_modules_installed(), so the
  # content appears as the module is installed.
  task "Installing Generated content module."
  GENERATED_CONTENT_CREATE=1 drush pm:install generated_content
  pass "Installed Generated content module."
fi
#;> MODULE_GENERATED_CONTENT

info "Finished development modules operations."
