#!/usr/bin/env bash
##
# Updated Drual-Dev.
#

set -e

# Allow to override tracked files in order to receive updates.
DRUPALDEV_ALLOW_OVERRIDE="${DRUPALDEV_ALLOW_OVERRIDE:-1}"

# Allow to provide custom Drupal-Dev commit hash to download the sources from.
DRUPALDEV_COMMIT="${DRUPALDEV_COMMIT:-}"

# The URL of the install script.
INSTALL_URL="${INSTALL_URL:-https://raw.githubusercontent.com/integratedexperts/drupal-dev/${DRUPAL_VERSION}.x/install.sh}"

# ------------------------------------------------------------------------------

# Use local (if provided) or remote install script.
if [ -n "${DRUPALDEV_INSTALL_SCRIPT+x}" ] && [ -f "${DRUPALDEV_INSTALL_SCRIPT}" ]; then
  bash "${DRUPALDEV_INSTALL_SCRIPT}" "$@";
else
  bash <(curl -L "${INSTALL_URL}"?"$(date +%s)") "$@";
fi

