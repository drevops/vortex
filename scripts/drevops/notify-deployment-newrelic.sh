#!/usr/bin/env bash
##
# New Relic deployment notification.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

[ "${SKIP_NOTIFY_DEPLOYMENT}" = "1" ] && echo "Skipping notify deployment." && exit 0

# The API key. Usually of type 'USER'.
NOTIFY_NEWRELIC_APIKEY="${NOTIFY_NEWRELIC_APIKEY:-}"

# Deployment reference, such as a git SHA.
NOTIFY_DEPLOY_REF="${NOTIFY_DEPLOY_REF:-}"

# Application name as it appears in the dashboard.
NOTIFY_NEWRELIC_APPNAME="${NOTIFY_NEWRELIC_APPNAME:-}"

# Optional Application ID. Will be discovered automatically from application name if not provided.
NOTIFY_NEWRELIC_APPID="${NOTIFY_NEWRELIC_APPID:-}"

# Optional deployment description.
NOTIFY_NEWRELIC_DESCRIPTION="${NOTIFY_NEWRELIC_DESCRIPTION:-"${NOTIFY_DEPLOY_REF} deployed"}"

# Optional deployment changelog. Defaults to description.
NOTIFY_NEWRELIC_CHANGELOG="${NOTIFY_NEWRELIC_CHANGELOG:-${NOTIFY_NEWRELIC_DESCRIPTION}}"

# Optional user name performing the deployment.
NOTIFY_NEWRELIC_USER="${NOTIFY_NEWRELIC_USER:-"Deployment robot"}"

# Optional endpoint.
NOTIFY_NEWRELIC_ENDPOINT="${NOTIFY_NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

# ------------------------------------------------------------------------------

[ -z "${NOTIFY_NEWRELIC_APIKEY}" ] && echo "ERROR: Missing required value for NOTIFY_NEWRELIC_APIKEY" && exit 1
[ -z "${NOTIFY_DEPLOY_REF}" ] && echo "ERROR: Missing required value for NOTIFY_DEPLOY_REF" && exit 1
[ -z "${NOTIFY_NEWRELIC_APPNAME}" ] && echo "ERROR: Missing required value for NOTIFY_NEWRELIC_APPNAME" && exit 1
[ -z "${NOTIFY_NEWRELIC_DESCRIPTION}" ] && echo "ERROR: Missing required value for NOTIFY_NEWRELIC_DESCRIPTION" && exit 1
[ -z "${NOTIFY_NEWRELIC_CHANGELOG}" ] && echo "ERROR: Missing required value for NOTIFY_NEWRELIC_CHANGELOG" && exit 1
[ -z "${NOTIFY_NEWRELIC_USER}" ] && echo "ERROR: Missing required value for NOTIFY_NEWRELIC_USER" && exit 1

echo "==> Started New Relic notification"

# Discover APP id by name if it was not provided.
if [ -z "${NOTIFY_NEWRELIC_APPID}" ] && [ -n "${NOTIFY_NEWRELIC_APPNAME}" ]; then
  NOTIFY_NEWRELIC_APPID="$(curl -s -X GET "${NOTIFY_NEWRELIC_ENDPOINT}/applications.json" \
    -H "Api-Key:${NOTIFY_NEWRELIC_APIKEY}" \
    -s -G -d "filter[name]=${NOTIFY_NEWRELIC_APPNAME}&exclude_links=true" |
    cut -c 24- |
    cut -c -10)"
fi

{ [ "${#NOTIFY_NEWRELIC_APPID}" != "10" ] || [ "$(expr "x$NOTIFY_NEWRELIC_APPID" : "x[0-9]*$")" -eq 0 ]; } && echo "ERROR: Failed to get an application ID from the application name ${NOTIFY_NEWRELIC_APPNAME}." && exit 1

if ! curl -X POST "${NOTIFY_NEWRELIC_ENDPOINT}/applications/${NOTIFY_NEWRELIC_APPID}/deployments.json" \
  -L -s -o /dev/null -w "%{http_code}" \
  -H "Api-Key:${NOTIFY_NEWRELIC_APIKEY}" \
  -H 'Content-Type: application/json' \
  -d \
  "{
  \"deployment\": {
    \"revision\": \"${NOTIFY_DEPLOY_REF}\",
    \"changelog\": \"${NOTIFY_NEWRELIC_CHANGELOG}\",
    \"description\": \"${NOTIFY_NEWRELIC_DESCRIPTION}\",
    \"user\": \"${NOTIFY_NEWRELIC_USER}\"
  }
}" | grep -q '201'; then
  error "ERROR: Failed to crate a deployment notification for application ${NOTIFY_NEWRELIC_APPNAME} with ID ${NOTIFY_NEWRELIC_APPID}"
  exit 1
fi

echo "==> Finished New Relic notification"
