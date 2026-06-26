#!/usr/bin/env bash
##
# Manual test script for Email notifications.
#
# Usage:
#   ./try-notify-email.sh [branch|pr]

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

# Require an explicit recipient so test mail never goes to an unintended address.
EMAIL_RECIPIENT="${EMAIL_RECIPIENT:-}"

if [ -z "${EMAIL_RECIPIENT}" ]; then
  echo "Error: EMAIL_RECIPIENT environment variable is required"
  echo "Usage: EMAIL_RECIPIENT=\"tester@example.com\" $0 [branch|pr]"
  exit 1
fi

echo "Testing Email notification..."
echo ""
echo "Email Recipient: ${EMAIL_RECIPIENT}"
echo ""

# Determine test scenario
SCENARIO="${1:-branch}"

case "${SCENARIO}" in
  pr)
    echo "Testing PR deployment notification"
    export VORTEX_NOTIFY_PROJECT="Test Project with PR"
    export VORTEX_NOTIFY_BRANCH="feature/PROJ-123-test-feature"
    export VORTEX_NOTIFY_SHA="abc123def456"
    export VORTEX_NOTIFY_PR_NUMBER="123"
    export VORTEX_NOTIFY_LABEL="PR-123"
    ;;
  branch)
    echo "Testing branch deployment notification"
    export VORTEX_NOTIFY_PROJECT="Test Project"
    export VORTEX_NOTIFY_BRANCH="main"
    export VORTEX_NOTIFY_SHA="abc123def456"
    export VORTEX_NOTIFY_LABEL="main"
    ;;
  *)
    echo "Error: scenario must be 'branch' or 'pr'"
    exit 1
    ;;
esac

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
./vendor/bin/vortex-notify

echo ""
echo "Check your email at ${EMAIL_RECIPIENT} for the notification!"
