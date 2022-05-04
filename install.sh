#!/usr/bin/env bash
##
# DrevOps legacy installer.
#
# Use install.php.
#
# Left here for compatibility with earlier DrevOps versions and will be removed
# in DrevOps 1.11.

{

set -e

DREVOPS_INSTALLER_URL="${DREVOPS_INSTALLER_URL:-https://raw.githubusercontent.com/drevops/drevops/${DREVOPS_DRUPAL_VERSION:-9}.x/install.php}"
DREVOPS_DRUPAL_VERSION="${DREVOPS_DRUPAL_VERSION:-9}"

echo "This install script has been deprecated and replaced with a new install script."
echo
echo "For interactive installation or update:"
echo "curl -L ${DREVOPS_INSTALLER_URL}?$(date +%s) > /tmp/install.php && php /tmp/install.php; rm /tmp/install.php >/dev/null"
echo
echo "For quiet installation or update:"
echo "curl -L ${DREVOPS_INSTALLER_URL}?$(date +%s) > /tmp/install.php && php /tmp/install.php --quiet; rm /tmp/install.php >/dev/null"
echo

}
