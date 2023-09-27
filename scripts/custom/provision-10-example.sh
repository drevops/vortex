#!/usr/bin/env bash
##
# Example of the custom per-project command that will run after website is installed.
#
# Clone this file and modify it to your needs or simply remove it.
#
# For ordering multiple commands, use a two-digit suffix for clarity and consistency.
# This approach ensures a clear sequence and avoids potential ordering issues.
# Examples:
# - provision-10-example.sh
# - provision-20-example.sh
# - provision-30-example.sh
#
# shellcheck disable=SC2086

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# Perform operations based on the current environment.
if echo "${DREVOPS_PROVISION_ENVIRONMENT:-}" | grep -q -e dev -e test -e ci -e local; then
  echo "[INFO] Executing example operations in non-production environment."

  drush() { ./vendor/bin/drush -y "$@"; }

  # Below are examples of running operations.

  # Set site name.
  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"

  # Enable custom site module and run its deployment hooks.
  #
  # In this example, the deployment hook implementation conditionally enables
  # other custom modules:
  # - Redis cache backend, if it is used in the project
  # - ClamAV, if it is used in the project
  # - Additional Solr search configuration, if Solr is used in the project
  #
  # Note that deployment hooks for already enabled modules have run in the parent script.
  drush pm:install ys_core
  drush deploy:hook

  # Conditionally perform an action if this is a "fresh" database.
  if [ "${DREVOPS_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
    echo "  > Fresh database detected. Performing additional operations."
  else
    echo "  > Existing database detected. Skipping additional operations."
  fi

  echo "[ OK ] Finished executing example operations in non-production environment."
fi
