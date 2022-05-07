#!/usr/bin/env bash
##
# Example of the custom per-project command that will run after website is installed.
#
# Clone this file and modify it to your needs or simply remove it.
#
# shellcheck disable=SC2086

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# ------------------------------------------------------------------------------

echo "==> Started example post site install operations."

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${DREVOPS_APP}/vendor/bin/drush" ]; then echo "${DREVOPS_APP}/vendor/bin/drush"; else command -v drush; fi)"

# Perform operations based on the current environment.
if $drush ev "print \Drupal\core\Site\Settings::get('environment');" | grep -q -e dev -e test -e ci -e local; then
  echo "  > Perform example operations in non-production environment."

  # @todo: Add your custom operations here.
fi

echo "==> Finished example post site install operations."
