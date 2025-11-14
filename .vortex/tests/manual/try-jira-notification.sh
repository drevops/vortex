#!/usr/bin/env bash
##
# Manual test script for JIRA notifications.
#
# Usage:
#   export JIRA_USER="your-email@example.com"
#   export JIRA_TOKEN="your-api-token"
#   export JIRA_ENDPOINT="https://your-domain.atlassian.net"
#   export JIRA_ISSUE="PROJ-123"
#   ./test-jira-notification.sh [branch|pr]

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

# Default values for testing (requires JIRA_TOKEN to be set externally)
JIRA_USER="${JIRA_USER:-alex@drevops.com}"
JIRA_TOKEN="${JIRA_TOKEN:-}"
JIRA_ENDPOINT="${JIRA_ENDPOINT:-https://drevops.atlassian.net}"
JIRA_ISSUE="${JIRA_ISSUE:-DEMO-2}"

# Check if JIRA_TOKEN is set
if [ -z "${JIRA_TOKEN}" ]; then
  echo "Error: JIRA_TOKEN environment variable is required"
  echo "Usage: export JIRA_TOKEN=\"your-api-token\" && $0 [branch|pr]"
  exit 1
fi

echo "Testing JIRA notification..."
echo ""
echo "JIRA Endpoint: ${JIRA_ENDPOINT}"
echo "JIRA User: ${JIRA_USER}"
echo "JIRA Issue: ${JIRA_ISSUE}"
echo ""

# Determine test scenario
SCENARIO="${1:-branch}"

if [ "${SCENARIO}" = "pr" ]; then
  echo "Testing PR deployment notification"
  export VORTEX_NOTIFY_PROJECT="Test Project with PR"
  export VORTEX_NOTIFY_LABEL="feature/${JIRA_ISSUE}-test-notification"
else
  echo "Testing branch deployment notification"
  export VORTEX_NOTIFY_PROJECT="Test Project"
  export VORTEX_NOTIFY_LABEL="feature/${JIRA_ISSUE}-test-notification"
fi

# Set required environment variables
export VORTEX_NOTIFY_CHANNELS=jira
export VORTEX_NOTIFY_JIRA_USER_EMAIL="${JIRA_USER}"
export VORTEX_NOTIFY_JIRA_TOKEN="${JIRA_TOKEN}"
export VORTEX_NOTIFY_JIRA_ENDPOINT="${JIRA_ENDPOINT}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_EVENT="post_deployment"

# Optional: Leave empty to skip transition and assignment
# Set to test transition and assignment:
export VORTEX_NOTIFY_JIRA_TRANSITION="${VORTEX_NOTIFY_JIRA_TRANSITION:-READY FOR QA}"
export VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL="${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL:-alex@drevops.com}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
echo "Check your JIRA issue ${JIRA_ISSUE} for the comment!"
