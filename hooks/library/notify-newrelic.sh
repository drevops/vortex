#!/usr/bin/env bash
##
# Acquia Cloud hook: Notify Newrelic.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

SITE="${1}"
TARGET_ENV="${2}"
DEPLOY_REF="${4}"

NEWRELIC_USER="${NEWRELIC_USER:-}"
NEWRELIC_APPID="${NEWRELIC_APPID:-}"
NEWRELIC_APIKEY="${NEWRELIC_APIKEY:-}"

# ------------------------------------------------------------------------------

CONFIG_FILE="${CONFIG_FILE:-${HOME}/newrelic.variables.sh}"
NEWRELIC_ENDPOINT="${NEWRELIC_ENDPOINT:-https://api.newrelic.com/deployments.xml}"

if [ -f "${CONFIG_FILE}" ]; then
  # shellcheck disable=SC1090
  . "${CONFIG_FILE}"

  [ -z "${NEWRELIC_USER}" ] && echo "Missing required value for \$NEWRELIC_USER" && exit 1
  [ -z "${NEWRELIC_APPID}" ] && echo "Missing required value for \$NEWRELIC_APPID" && exit 1
  [ -z "${NEWRELIC_APIKEY}" ] && echo "Missing required value for \$NEWRELIC_APIKEY" && exit 1

  curl -s \
    -H "x-api-key:${NEWRELIC_APIKEY}" \
    -d "deployment[application_id]=${NEWRELIC_APPID}" \
    -d "deployment[host]=localhost" \
    -d "deployment[description]=${DEPLOY_REF} deployed to $SITE:$TARGET_ENV" \
    -d "deployment[revision]=$DEPLOY_REF" \
    -d "deployment[changelog]=$DEPLOY_REF deployed to $SITE:$TARGET_ENV" \
    -d "deployment[user]=${NEWRELIC_USER}" \
    "${NEWRELIC_ENDPOINT}"
else
  echo "Config file ${CONFIG_FILE} does not exist. NewRelic notification is not sent."
fi
