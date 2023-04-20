#!/usr/bin/env bash
##
# Print project information.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to the root of the project inside the container.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# Show Drupal one-time login link.
DREVOPS_DRUPAL_SHOW_LOGIN_LINK="${DREVOPS_DRUPAL_SHOW_LOGIN_LINK:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

echo
note "Project name                : ${DREVOPS_PROJECT}"
note "Docker Compose project name : ${COMPOSE_PROJECT_NAME:-}"
note "Site local URL              : http://${DREVOPS_LOCALDEV_URL}"
note "Path to project             : ${DREVOPS_APP}"
note "Path to web root            : ${DREVOPS_APP}/${DREVOPS_WEBROOT}"
note "DB host                     : ${DREVOPS_MARIADB_HOST}"
note "DB username                 : ${DREVOPS_MARIADB_USER}"
note "DB password                 : ${DREVOPS_MARIADB_PASSWORD}"
note "DB port                     : ${DREVOPS_MARIADB_PORT}"
note "DB port on host             : ${DREVOPS_HOST_DB_PORT} ('ahoy db' to start SequelAce)"
if [ -n "${DREVOPS_DB_DOCKER_IMAGE}" ]; then
  note "DB-in-docker image          : ${DREVOPS_DB_DOCKER_IMAGE}"
fi
if [ -n "${DREVOPS_HOST_SOLR_PORT}" ]; then
  note "Solr URL on host            : http://127.0.0.1:${DREVOPS_HOST_SOLR_PORT}"
fi
note "Mailhog URL                 : http://mailhog.docker.amazee.io/"
note "Xdebug                      : $(php -v | grep -q Xdebug && echo "Enabled ('ahoy up cli' to disable)" || echo "Disabled ('ahoy debug' to enable)")"
if [ "${DREVOPS_DRUPAL_SHOW_LOGIN_LINK}" = "1" ] || [ -n "${1}" ]; then
  echo -n "       Site login link             : "
  ./scripts/drevops/drupal-login.sh
fi
echo
