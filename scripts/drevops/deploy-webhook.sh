#!/usr/bin/env bash
##
# Deploy via calling a webhook.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The URL of the webhook to call. Note that any tokens should be added to
# to the value of this variable outside of this script.
DEPLOY_WEBHOOK_URL="${DEPLOY_WEBHOOK_URL:-}"

# The status code of the expected response.
DEPLOY_WEBHOOK_RESPONSE_STATUS=${DEPLOY_WEBHOOK_RESPONSE_STATUS:-}

# ------------------------------------------------------------------------------

echo "==> Started WEBHOOK deployment."

# Check all required values.
[ -z "${DEPLOY_WEBHOOK_URL}" ] && echo "Missing required value for DEPLOY_WEBHOOK_URL." && exit 1
[ -z "${DEPLOY_WEBHOOK_RESPONSE_STATUS}" ] && echo "Missing required value for DEPLOY_WEBHOOK_RESPONSE_STATUS." && exit 1

# Note that we do not output ${DEPLOY_WEBHOOK_URL} as it may contain secrets
# that would be printed to the terminal.
if curl -L -s -o /dev/null -w "%{http_code}" "${DEPLOY_WEBHOOK_URL}" | grep -q "${DEPLOY_WEBHOOK_RESPONSE_STATUS}"; then
  echo "==> Successfully called webhook."
else
  echo "ERROR: Webhook deployment failed."
  exit 1
fi

echo "==> Finished WEBHOOK deployment."
