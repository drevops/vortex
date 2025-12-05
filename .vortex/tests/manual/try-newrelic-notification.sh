#!/usr/bin/env bash
##
# Manual test script for New Relic notifications.
#
# Usage:
#   export NEWRELIC_USER_KEY="your-user-api-key"
#   export NEWRELIC_APP_NAME="your-app-name"  # Optional (default: Test Project-main)
#   export NEWRELIC_ENDPOINT="https://api.newrelic.com/v2"  # Optional
#   ./try-newrelic-notification.sh [branch|pr]

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

# Default values for testing (requires NEWRELIC_USER_KEY to be set externally)
NEWRELIC_USER_KEY="${NEWRELIC_USER_KEY:-}"
NEWRELIC_ENDPOINT="${NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

# Check if NEWRELIC_USER_KEY is set
if [ -z "${NEWRELIC_USER_KEY}" ]; then
  echo "Error: NEWRELIC_USER_KEY environment variable is required"
  echo "Usage: export NEWRELIC_USER_KEY=\"your-user-api-key\" && $0 [branch|pr]"
  exit 1
fi

echo "Testing New Relic notification..."
echo ""
echo "Endpoint: ${NEWRELIC_ENDPOINT}"
echo "API User Key: ${NEWRELIC_USER_KEY:0:20}..."
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
  NEWRELIC_APP_NAME="${NEWRELIC_APP_NAME:-Test Project with PR-PR-123}"
else
  echo "Testing branch deployment notification"
  export VORTEX_NOTIFY_PROJECT="Test Project"
  export VORTEX_NOTIFY_BRANCH="main"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="main"
  NEWRELIC_APP_NAME="${NEWRELIC_APP_NAME:-Test Project-main}"
fi

echo "Application name: ${NEWRELIC_APP_NAME}"
echo ""

# Set required environment variables
export VORTEX_NOTIFY_CHANNELS=newrelic
export VORTEX_NOTIFY_NEWRELIC_USER_KEY="${NEWRELIC_USER_KEY}"
export VORTEX_NOTIFY_NEWRELIC_ENDPOINT="${NEWRELIC_ENDPOINT}"
export VORTEX_NOTIFY_NEWRELIC_APP_NAME="${NEWRELIC_APP_NAME}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_EVENT="post_deployment"

# Optional: Customize deployment user
export VORTEX_NOTIFY_NEWRELIC_USER="${VORTEX_NOTIFY_NEWRELIC_USER:-Deployment Bot}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
echo "Check your New Relic dashboard for the deployment marker!"
echo "If the application '${NEWRELIC_APP_NAME}' doesn't exist, the notification will be skipped."
