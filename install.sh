#!/usr/bin/env bash
##
# DrevOps legacy installer.
#
# Use install.php.
#
# Left here for compatibility for earlier DrevOps versions and will be removed
# in DrevOps 1.10.

{

set -e

DRUPAL_VERSION="${DRUPAL_VERSION:-7}"
INSTALL_URL="${INSTALL_URL:-https://raw.githubusercontent.com/drevops/drevops/${DRUPAL_VERSION:-7}.x/install.php}"

echo "This install script has been deprecated and replaced with a new install script."
echo
echo "For silent installation or update:"
echo "curl -L ${INSTALL_URL}?$(date +%s) > /tmp/install.php && php /tmp/install.php; rm /tmp/install.php"
echo
echo "For interactive installation or update:"
echo "curl -L ${INSTALL_URL}?$(date +%s) > /tmp/install.php && php /tmp/install.php --interactive; rm /tmp/install.php"
echo

}
