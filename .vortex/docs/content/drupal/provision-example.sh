#!/usr/bin/env bash
##
# Example of the custom per-project command that will run after website is installed.
#
# Clone this file and modify it to your needs or simply remove it.
#
# For ordering multiple commands, use a two-digit suffix for clarity and consistency.
# This approach ensures a clear sequence and avoids potential ordering issues.
#
# Example:
# - provision-10-example.sh
# - provision-20-example.sh
# - provision-30-example.sh
#
# shellcheck disable=SC2086

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

info() { printf "   ==> %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }

drush() { ./vendor/bin/drush -y "$@"; }

info "Started example operations."

# 👇 Get the current environment from Drupal settings.
environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# 👇 Perform operations based on the current environment.
if echo "${environment}" | grep -q -e dev -e stage -e ci -e local; then
  note "Running example operations in non-production environment."

  # 👇 Enable custom site modules and run its deployment hooks.
  task "Installing custom site modules."
  drush pm:install ys_base ys_search

  # 👇 Conditionally perform an action if this is a "fresh" database.
  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
    note "Fresh database detected. Performing additional example operations."
  else
    note "Existing database detected. Performing additional example operations."
  fi
else
  note "Skipping example operations in production environment."
fi

info "Finished example operations."
