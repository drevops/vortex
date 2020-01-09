#!/usr/bin/env bash
##
# Acquia Cloud hook: Notify Newrelic.
#

set -e
set -x

site=$1
target_env=$2
deploy_ref=$4

CONFIG_FILE="${CONFIG_FILE:-$HOME/newrelic.variables.sh}"
NEWRELIC_ENDPOINT="${NEWRELIC_ENDPOINT:-https://api.newrelic.com/deployments.xml}"

NEWRELIC_USER="${NEWRELIC_USER:-}"
NEWRELIC_APPID="${NEWRELIC_APPID:-}"
NEWRELIC_APIKEY="${NEWRELIC_APIKEY:-}"

if [ -f "${CONFIG_FILE}" ]; then
  # shellcheck disable=SC1090
  . "${CONFIG_FILE}"

  [ -z "${NEWRELIC_USER}" ] && echo "Missing required value for NEWRELIC_USER" && exit 1
  [ -z "${NEWRELIC_APPID}" ] && echo "Missing required value for NEWRELIC_APPID" && exit 1
  [ -z "${NEWRELIC_APIKEY}" ] && echo "Missing required value for NEWRELIC_APIKEY" && exit 1

  curl -s \
    -H "x-api-key:${NEWRELIC_APIKEY}" \
    -d "deployment[application_id]=${NEWRELIC_APPID}" \
    -d "deployment[host]=localhost" \
    -d "deployment[description]=${deploy_ref} deployed to $site:$target_env" \
    -d "deployment[revision]=$deploy_ref" \
    -d "deployment[changelog]=$deploy_ref deployed to $site:$target_env" \
    -d "deployment[user]=${NEWRELIC_USER}" \
    "${NEWRELIC_ENDPOINT}"
else
  echo "Config file ${CONFIG_FILE} does not exist. NewRelic notification is not sent."
fi
