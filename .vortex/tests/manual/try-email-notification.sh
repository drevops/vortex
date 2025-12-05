#!/usr/bin/env bash
##
# Manual test script for Email notifications.
#
# Usage:
#   ./try-email-notification.sh [branch|pr]

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

# Default email for testing
EMAIL_RECIPIENT="${EMAIL_RECIPIENT:-alex+vortex@drevops.com}"

echo "Testing Email notification..."
echo ""
echo "Email Recipient: ${EMAIL_RECIPIENT}"
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
export VORTEX_NOTIFY_CHANNELS=email
export VORTEX_NOTIFY_EMAIL_FROM="${EMAIL_RECIPIENT}"
export VORTEX_NOTIFY_EMAIL_RECIPIENTS="${EMAIL_RECIPIENT}|Vortex Test"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_EVENT="post_deployment"

# Optional: Set Drupal site email if needed
export DRUPAL_SITE_EMAIL="${EMAIL_RECIPIENT}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
echo "Check your email at ${EMAIL_RECIPIENT} for the notification!"
