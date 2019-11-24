#!/usr/bin/env bash
##
# Updated Drual-Dev.
#

set -e

# Allow to override tracked files in order to receive updates.
DREVOPS_ALLOW_OVERRIDE="${DREVOPS_ALLOW_OVERRIDE:-1}"

# Allow to provide custom DrevOps commit hash to download the sources from.
DREVOPS_COMMIT="${DREVOPS_COMMIT:-}"

# The URL of the install script.
INSTALL_URL="${INSTALL_URL:-https://raw.githubusercontent.com/drevops/drevops/${DRUPAL_VERSION}.x/install.sh}"

# ------------------------------------------------------------------------------

# Use local (if provided) or remote install script.
if [ -n "${DREVOPS_INSTALL_SCRIPT+x}" ] && [ -f "${DREVOPS_INSTALL_SCRIPT}" ]; then
  bash "${DREVOPS_INSTALL_SCRIPT}" "$@";
else
  bash <(curl -L "${INSTALL_URL}"?"$(date +%s)") "$@";
fi

