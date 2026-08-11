#!/usr/bin/env bash
##
# Example of a custom per-project command that runs after the site is installed.
#
# Clone this file and modify it as needed or simply remove it.
#
# For ordering multiple commands, use a two-digit suffix.
#
# Example:
# - provision-40-example.sh
# - provision-50-example.sh
# - provision-60-example.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     < %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started example operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

if ! echo "${environment}" | grep -qxF -e local -e ci -e dev -e stage; then
  note "Skipped example operations in production environment."
  exit 0
fi

note "Running example operations in non-production environment."

task "Performing an example operation."
note "Replace this with your own commands."
pass "Performed an example operation."

# Branch on whether the database was freshly imported.
if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
  note "Fresh database detected. Performing additional example operations."
else
  note "Existing database detected. Performing additional example operations."
fi

info "Finished example operations."
