#!/usr/bin/env bash
##
# New Relic deployment notification.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The API key. Usually of type 'USER'.
DREVOPS_NOTIFY_NEWRELIC_APIKEY="${DREVOPS_NOTIFY_NEWRELIC_APIKEY:-}"

# Deployment reference, such as a git SHA.
DREVOPS_NOTIFY_DEPLOY_REF="${DREVOPS_NOTIFY_DEPLOY_REF:-}"

# Application name as it appears in the dashboard.
DREVOPS_NOTIFY_NEWRELIC_APP_NAME="${DREVOPS_NOTIFY_NEWRELIC_APP_NAME:-}"

# Optional Application ID. Will be discovered automatically from application name if not provided.
DREVOPS_NOTIFY_NEWRELIC_APPID="${DREVOPS_NOTIFY_NEWRELIC_APPID:-}"

# Optional deployment description.
DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION="${DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION:-"${DREVOPS_NOTIFY_DEPLOY_REF} deployed"}"

# Optional deployment changelog. Defaults to description.
DREVOPS_NOTIFY_NEWRELIC_CHANGELOG="${DREVOPS_NOTIFY_NEWRELIC_CHANGELOG:-${DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION}}"

# Optional user name performing the deployment.
DREVOPS_NOTIFY_NEWRELIC_USER="${DREVOPS_NOTIFY_NEWRELIC_USER:-"Deployment robot"}"

# Optional endpoint.
DREVOPS_NOTIFY_NEWRELIC_ENDPOINT="${DREVOPS_NOTIFY_NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

# Flag to skip notification.
DREVOPS_NOTIFY_DEPLOYMENT_SKIP="${DREVOPS_NOTIFY_DEPLOYMENT_SKIP:-}"

# Flag to skip NewRelic deployment notification.
DREVOPS_NOTIFY_DEPLOY_NEWRELIC_SKIP="${DREVOPS_NOTIFY_DEPLOY_NEWRELIC_SKIP:-}"

# ------------------------------------------------------------------------------

{ [ "${DREVOPS_NOTIFY_DEPLOYMENT_SKIP}" = "1" ] || [ "${DREVOPS_NOTIFY_DEPLOY_NEWRELIC_SKIP}" = "1" ]; } && echo "Skipping NewRelic notification of deployment." && exit 0

[ -z "${DREVOPS_NOTIFY_NEWRELIC_APIKEY}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_NEWRELIC_APIKEY" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_REF}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_REF" && exit 1
[ -z "${DREVOPS_NOTIFY_NEWRELIC_APP_NAME}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_NEWRELIC_APP_NAME" && exit 1
[ -z "${DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION" && exit 1
[ -z "${DREVOPS_NOTIFY_NEWRELIC_CHANGELOG}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_NEWRELIC_CHANGELOG" && exit 1
[ -z "${DREVOPS_NOTIFY_NEWRELIC_USER}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_NEWRELIC_USER" && exit 1

echo "==> Started New Relic notification"

# Discover APP id by name if it was not provided.
if [ -z "${DREVOPS_NOTIFY_NEWRELIC_APPID}" ] && [ -n "${DREVOPS_NOTIFY_NEWRELIC_APP_NAME}" ]; then
  DREVOPS_NOTIFY_NEWRELIC_APPID="$(curl -s -X GET "${DREVOPS_NOTIFY_NEWRELIC_ENDPOINT}/applications.json" \
    -H "Api-Key:${DREVOPS_NOTIFY_NEWRELIC_APIKEY}" \
    -s -G -d "filter[name]=${DREVOPS_NOTIFY_NEWRELIC_APP_NAME}&exclude_links=true" |
    cut -c 24- |
    cut -c -10)"
fi

{ [ "${#DREVOPS_NOTIFY_NEWRELIC_APPID}" != "10" ] || [ "$(expr "x$DREVOPS_NOTIFY_NEWRELIC_APPID" : "x[0-9]*$")" -eq 0 ]; } && echo "ERROR: Failed to get an application ID from the application name ${DREVOPS_NOTIFY_NEWRELIC_APP_NAME}." && exit 1

if ! curl -X POST "${DREVOPS_NOTIFY_NEWRELIC_ENDPOINT}/applications/${DREVOPS_NOTIFY_NEWRELIC_APPID}/deployments.json" \
  -L -s -o /dev/null -w "%{http_code}" \
  -H "Api-Key:${DREVOPS_NOTIFY_NEWRELIC_APIKEY}" \
  -H 'Content-Type: application/json' \
  -d \
  "{
  \"deployment\": {
    \"revision\": \"${DREVOPS_NOTIFY_DEPLOY_REF}\",
    \"changelog\": \"${DREVOPS_NOTIFY_NEWRELIC_CHANGELOG}\",
    \"description\": \"${DREVOPS_NOTIFY_NEWRELIC_DESCRIPTION}\",
    \"user\": \"${DREVOPS_NOTIFY_NEWRELIC_USER}\"
  }
}" | grep -q '201'; then
  error "ERROR: Failed to crate a deployment notification for application ${DREVOPS_NOTIFY_NEWRELIC_APP_NAME} with ID ${DREVOPS_NOTIFY_NEWRELIC_APPID}"
  exit 1
fi

echo "==> Finished New Relic notification"
