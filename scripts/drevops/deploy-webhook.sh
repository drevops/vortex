#!/usr/bin/env bash
##
# Deploy by calling a webhook.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The URL of the webhook to call.
# Note that any tokens should be added to the value of this variable outside
# this script.
DREVOPS_DEPLOY_WEBHOOK_URL="${DREVOPS_DEPLOY_WEBHOOK_URL:-}"

# Webhook call method.
DREVOPS_DEPLOY_WEBHOOK_METHOD="${DREVOPS_DEPLOY_WEBHOOK_METHOD:-GET}"

# The status code of the expected response.
DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS=${DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS:-200}

# ------------------------------------------------------------------------------

echo "==> Started WEBHOOK deployment."

# Check all required values.
[ -z "${DREVOPS_DEPLOY_WEBHOOK_URL}" ] && echo "Missing required value for DREVOPS_DEPLOY_WEBHOOK_URL." && exit 1
[ -z "${DREVOPS_DEPLOY_WEBHOOK_METHOD}" ] && echo "Missing required value for DREVOPS_DEPLOY_WEBHOOK_METHOD." && exit 1
[ -z "${DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS}" ] && echo "Missing required value for DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS." && exit 1

if curl -X "${DREVOPS_DEPLOY_WEBHOOK_METHOD}" -L -s -o /dev/null -w "%{http_code}" "${DREVOPS_DEPLOY_WEBHOOK_URL}" | grep -q "${DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS}"; then
  # Note that we do not output ${DREVOPS_DEPLOY_WEBHOOK_URL} as it may contain
  # secrets that would be printed to the terminal.
  echo "  > Successfully called webhook."
else
  echo "ERROR: Webhook deployment failed."
  exit 1
fi

echo "==> Finished WEBHOOK deployment."
