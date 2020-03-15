#!/usr/bin/env bash
##
# Updated Drual-Dev.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Allow to override tracked files in order to receive updates.
export DREVOPS_ALLOW_OVERRIDE="${DREVOPS_ALLOW_OVERRIDE:-1}"

# Allow to provide custom DrevOps commit hash to download the sources from.
export DREVOPS_COMMIT="${DREVOPS_COMMIT:-}"

# The URL of the install script.
export INSTALL_URL="${INSTALL_URL:-https://raw.githubusercontent.com/drevops/drevops/${DRUPAL_VERSION:-7}.x/install.php}"

# ------------------------------------------------------------------------------
#set -x
# Use local (if provided) or remote install script.
if [ -n "${DREVOPS_INSTALL_SCRIPT+x}" ] && [ -f "${DREVOPS_INSTALL_SCRIPT}" ]; then
  php "${DREVOPS_INSTALL_SCRIPT}";
else
  curl -L "${INSTALL_URL}"?"$(date +%s)" > /tmp/install.php && php /tmp/install.php; rm /tmp/install.php
fi

