#!/usr/bin/env bash
##
# Manual test script for Slack notifications.
#
# Usage:
#   export SLACK_WEBHOOK_URL="your-webhook-url"
#   ./try-slack-notification.sh [branch|pr]

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

# Default webhook URL for testing (requires SLACK_WEBHOOK_URL to be set externally)
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"

# Check if SLACK_WEBHOOK_URL is set
if [ -z "${SLACK_WEBHOOK_URL}" ]; then
  echo "Error: SLACK_WEBHOOK_URL environment variable is required"
  echo "Usage: export SLACK_WEBHOOK_URL=\"your-webhook-url\" && $0 [branch|pr]"
  exit 1
fi

echo "Testing Slack notification..."
echo ""
echo "Webhook URL: ${SLACK_WEBHOOK_URL:0:50}..."
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
export VORTEX_NOTIFY_CHANNELS=slack
export VORTEX_NOTIFY_SLACK_WEBHOOK="${SLACK_WEBHOOK_URL}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_EVENT="post_deployment"

# Optional: Customize these
export VORTEX_NOTIFY_SLACK_CHANNEL="${VORTEX_NOTIFY_SLACK_CHANNEL:-}"
export VORTEX_NOTIFY_SLACK_USERNAME="${VORTEX_NOTIFY_SLACK_USERNAME:-Deployment Bot}"
export VORTEX_NOTIFY_SLACK_ICON_EMOJI="${VORTEX_NOTIFY_SLACK_ICON_EMOJI:-:rocket:}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
echo "Check your Slack channel for the notification!"
