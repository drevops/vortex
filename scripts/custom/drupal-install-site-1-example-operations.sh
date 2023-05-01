#!/usr/bin/env bash
##
# Example of the custom per-project command that will run after website is installed.
#
# Clone this file and modify it to your needs or simply remove it.
#
# Add numbered suffix to order multiple commands.
#
# shellcheck disable=SC2086

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Path to the application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Perform operations based on the current environment.
if $drush php:eval "print \Drupal\core\Site\Settings::get('environment');" | grep -q -e dev -e test -e ci -e local; then
  echo "       Executing example operations in non-production environment."
  # Example operations.
  # Set site name.
  $drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
  # Enable custom site core module.
  $drush -y pm:install ys_core
  # Run deployment hooks defined in recently enabled modules (ys_core).
  # These hooks already ran for previously enabled modules.
  $drush -y deploy:hook
  echo "       Finished executing example operations in non-production environment."
fi
