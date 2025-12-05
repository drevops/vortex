#!/usr/bin/env bash
##
# Manual test script for Webhook notifications.
#
# Usage:
#   export WEBHOOK_URL="https://webhook.site/your-unique-id"
#   ./try-webhook-notification.sh [branch|pr]

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Get the directory of this script and navigate to project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"

# Default webhook URL for testing (requires WEBHOOK_URL to be set externally)
WEBHOOK_URL="${WEBHOOK_URL:-}"

# Check if WEBHOOK_URL is set
if [ -z "${WEBHOOK_URL}" ]; then
  echo "Error: WEBHOOK_URL environment variable is required"
  echo "Usage: export WEBHOOK_URL=\"https://webhook.site/your-unique-id\" && $0 [branch|pr]"
  exit 1
fi

echo "Testing Webhook notification..."
echo ""
echo "Webhook URL: ${WEBHOOK_URL}"
echo ""

# Determine test scenario
SCENARIO="${1:-branch}"

if [ "${SCENARIO}" = "pr" ]; then
  echo "Testing PR deployment notification"
  export VORTEX_NOTIFY_PROJECT="Test Project with PR"
  export VORTEX_NOTIFY_BRANCH="feature/PROJ-123-test-feature"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_PR_NUMBER="123"
  export VORTEX_NOTIFY_LABEL="PR-123"
else
  echo "Testing branch deployment notification"
  export VORTEX_NOTIFY_PROJECT="Test Project"
  export VORTEX_NOTIFY_BRANCH="main"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="main"
fi

# Set required environment variables
export VORTEX_NOTIFY_CHANNELS=webhook
export VORTEX_NOTIFY_WEBHOOK_URL="${WEBHOOK_URL}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_EVENT="post_deployment"

# Optional: Customize these
export VORTEX_NOTIFY_WEBHOOK_METHOD="${VORTEX_NOTIFY_WEBHOOK_METHOD:-POST}"
export VORTEX_NOTIFY_WEBHOOK_HEADERS="${VORTEX_NOTIFY_WEBHOOK_HEADERS:-Content-type: application/json}"
export VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS="${VORTEX_NOTIFY_WEBHOOK_RESPONSE_STATUS:-200}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
echo "Check your webhook.site page for the notification payload!"
