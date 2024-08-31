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

drush() { ./vendor/bin/drush -y "$@"; }

# Perform operations based on the current environment.
if echo "${VORTEX_PROVISION_ENVIRONMENT:-}" | grep -q -e dev -e test -e ci -e local; then
  echo "==> Executing example operations in non-production environment."

  # Below are examples of running operations.

  # Set site name.
  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"

  # Enable contrib modules.
  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy

  #;< REDIS
  drush pm:install redis
  #;> REDIS

  #;< CLAMAV
  drush pm:install clamav
  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
  #;> CLAMAV

  #;< SOLR
  drush pm:install search_api search_api_solr
  #;> SOLR

  # Enable custom site module and run its deployment hooks.
  #
  # Note that deployment hooks for already enabled modules have run in the
  # parent "provision.sh" script.
  drush pm:install ys_core ys_search
  drush deploy:hook

  # Conditionally perform an action if this is a "fresh" database.
  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
    echo "  > Fresh database detected. Performing additional example operations."
  else
    echo "  > Existing database detected. Performing additional example operations."
  fi

  echo "==> Finished executing example operations in non-production environment."
fi
