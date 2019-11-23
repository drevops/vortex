#!/usr/bin/env bash
##
# Print project information.
#

set -e

SHOW_LOGIN_LINK="${SHOW_LOGIN_LINK:-}"

# ------------------------------------------------------------------------------

echo  "Project                  : $PROJECT"
echo  "Site local URL           : http://$LOCALDEV_URL"
echo  "Path to project          : $APP"
echo  "Path to docroot          : $APP/$WEBROOT"
echo  "DB host                  : $AMAZEEIO_DB_HOST"
echo  "DB username              : $AMAZEEIO_DB_USERNAME"
echo  "DB password              : $AMAZEEIO_DB_PASSWORD"
echo  "DB port                  : $AMAZEEIO_DB_PORT"
echo  "DB port on host          : $HOST_DB_PORT"
if [ -n "${HOST_SOLR_PORT}" ]; then
echo  "Solr port on host        : $HOST_SOLR_PORT"
fi
echo  "Mailhog URL              : http://mailhog.docker.amazee.io/"
echo  "Xdebug                   : $(php -v | grep -q Xdebug && echo "Enabled" || echo "Disabled")"
# For performance, generate one-time login link only if explicitly requested.
if [ -n "$SHOW_LOGIN_LINK" ] || [ -n "${1}" ]; then
  echo  "One-time login           : $(drush uublk 1 -q && drush uli -l "${LOCALDEV_URL}" --no-browser)"
fi
