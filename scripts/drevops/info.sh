#!/usr/bin/env bash
##
# Print project information.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the application.
APP="${APP:-/app}"

SHOW_LOGIN_LINK="${SHOW_LOGIN_LINK:-}"

# ------------------------------------------------------------------------------

# Use local or global Drush, giving priority to a local drush.
drush="$(if [ -f "${APP}/vendor/bin/drush" ]; then echo "${APP}/vendor/bin/drush"; else command -v drush; fi)"

echo  "Project                  : ${PROJECT}"
echo  "Site local URL           : http://${LOCALDEV_URL}"
echo  "Path to project          : ${APP}"
echo  "Path to docroot          : ${APP}/${WEBROOT}"
echo  "DB host                  : ${MARIADB_HOST}"
echo  "DB username              : ${MARIADB_USER}"
echo  "DB password              : ${MARIADB_PASSWORD}"
echo  "DB port                  : ${MARIADB_PORT}"
echo  "DB port on host          : ${HOST_DB_PORT}"
if [ -n "${HOST_SOLR_PORT}" ]; then
  echo  "Solr port on host        : ${HOST_SOLR_PORT}"
fi
echo  "Mailhog URL              : http://mailhog.docker.amazee.io/"
echo  "Xdebug                   : $(php -v | grep -q Xdebug && echo "Enabled" || echo "Disabled")"
# For performance, generate one-time login link only if explicitly requested.
if [ -n "${SHOW_LOGIN_LINK}" ] || [ -n "${1}" ]; then
  echo  "One-time login           : $($drush uublk 1 -q && $drush uli -l "${LOCALDEV_URL}" --no-browser)"
fi
