#!/usr/bin/env bash
##
# Print project information.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
DREVOPS_APP="${APP:-/app}"

# Show Drupal one-time login link.
DREVOPS_DRUPAL_SHOW_LOGIN_LINK="${DREVOPS_DRUPAL_SHOW_LOGIN_LINK:-}"

# ------------------------------------------------------------------------------

echo  "Project                  : ${DREVOPS_PROJECT}"
echo  "Site local URL           : http://${DREVOPS_DOCTOR_LOCALDEV_URL}"
echo  "Path to project          : ${DREVOPS_APP}"
echo  "Path to docroot          : ${DREVOPS_APP}/docroot"
echo  "DB host                  : ${DREVOPS_MARIADB_HOST}"
echo  "DB username              : ${DREVOPS_MARIADB_USER}"
echo  "DB password              : ${DREVOPS_MARIADB_PASSWORD}"
echo  "DB port                  : ${DREVOPS_MARIADB_PORT}"
echo  "DB port on host          : ${DREVOPS_HOST_DB_PORT}"
if [ -n "${DREVOPS_HOST_SOLR_PORT}" ]; then
  echo  "Solr port on host        : ${DREVOPS_HOST_SOLR_PORT}"
fi
echo  "Mailhog URL              : http://mailhog.docker.amazee.io/"
echo  "Xdebug                   : $(php -v | grep -q Xdebug && echo "Enabled" || echo "Disabled")"

if [ "${DREVOPS_DRUPAL_SHOW_LOGIN_LINK}" = "1" ] || [ -n "${1}" ]; then
  echo -n "One-time login           : "
  ./scripts/drevops/drupal-login.sh
fi
