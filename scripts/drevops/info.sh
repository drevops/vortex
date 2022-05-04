#!/usr/bin/env bash
##
# Print project information.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

# Show login link.
DREVOPS_SHOW_LOGIN_LINK="${DREVOPS_SHOW_LOGIN_LINK:-}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

echo  "Project                  : ${DREVOPS_PROJECT}"
echo  "Site local URL           : http://${DREVOPS_LOCALDEV_URL}"
echo  "Path to project          : ${APP}"
echo  "Path to docroot          : ${APP}/docroot"
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
# For performance, generate one-time login link only if explicitly requested.
if [ "${DREVOPS_SHOW_LOGIN_LINK}" = "1" ] || [ -n "${1}" ]; then
  echo  "One-time login           : $($drush uublk 1 -q && $drush uli -l "${DREVOPS_LOCALDEV_URL}" --no-browser)"
fi
