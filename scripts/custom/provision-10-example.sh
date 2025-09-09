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

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Perform operations based on the current environment.
if echo "${environment}" | grep -q -e dev -e stage -e ci -e local; then
  note "Running example operations in non-production environment."

  # Set site name.
  task "Setting site name."
  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"

  # Enable contrib modules.
  task "Installing contrib modules."
  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect robotstxt shield stage_file_proxy

  #;< SERVICE_REDIS
  task "Installing Redis module."
  drush pm:install redis || true
  #;> SERVICE_REDIS

  #;< SERVICE_CLAMAV
  task "Installing and configuring ClamAV."
  drush pm:install clamav
  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
  #;> SERVICE_CLAMAV

  #;< SERVICE_SOLR
  task "Installing Solr search modules."
  drush pm:install search_api search_api_solr
  #;> SERVICE_SOLR

  # Enable custom site module and run its deployment hooks.
  #
  # Note that deployment hooks for already enabled modules have run in the
  # parent "provision.sh" script.
  task "Installing custom site modules."
  drush pm:install ys_base

  #;< SERVICE_SOLR
  drush pm:install ys_search
  #;> SERVICE_SOLR

  task "Running deployment hooks."
  drush deploy:hook

  # Conditionally perform an action if this is a "fresh" database.
  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
    note "Fresh database detected. Performing additional example operations."
  else
    note "Existing database detected. Performing additional example operations."
  fi
else
  note "Skipping example operations in production environment."
fi

info "Finished example operations."
