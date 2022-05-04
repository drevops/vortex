#!/usr/bin/env bash
##
# Update DrevOps.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The URL of the installer script.
export DREVOPS_INSTALLER_URL="${DREVOPS_INSTALLER_URL:-https://raw.githubusercontent.com/drevops/drevops/${DREVOPS_DRUPAL_VERSION:-9}.x/install.php}"

# Allow providing custom DrevOps commit hash to download the sources from.
export DREVOPS_COMMIT="${DREVOPS_COMMIT:-}"

# ------------------------------------------------------------------------------

curl -L "${DREVOPS_INSTALLER_URL}"?"$(date +%s)" > /tmp/install.php && php /tmp/install.php --quiet; rm /tmp/install.php;

