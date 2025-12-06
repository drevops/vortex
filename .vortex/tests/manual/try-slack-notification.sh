#!/usr/bin/env bash
##
# Manual test script for Slack notifications.
#
# Sends all 4 notification scenarios to Slack:
# 1. Branch pre-deployment (deployment starting, no links)
# 2. Branch post-deployment (deployment complete, with links)
# 3. PR pre-deployment (deployment starting, no links)
# 4. PR post-deployment (deployment complete, with links)
#
# Usage:
#   export SLACK_WEBHOOK_URL="your-webhook-url"
#   ./try-slack-notification.sh

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
  echo "Usage: export SLACK_WEBHOOK_URL=\"your-webhook-url\" && $0"
  exit 1
fi

echo "Testing Slack notifications..."
echo ""
echo "Webhook URL: ${SLACK_WEBHOOK_URL:0:50}..."
echo ""
echo "This will send 4 notifications to Slack:"
echo "  1. Branch pre-deployment (no View Site or Login links)"
echo "  2. Branch post-deployment (with View Site and Login links)"
echo "  3. PR pre-deployment (no View Site or Login links)"
echo "  4. PR post-deployment (with View Site and Login links)"
echo ""

# Change to project root
cd "${PROJECT_ROOT}" || exit 1

# Common settings
export VORTEX_NOTIFY_CHANNELS=slack
export VORTEX_NOTIFY_SLACK_WEBHOOK="${SLACK_WEBHOOK_URL}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://example.com"
export VORTEX_NOTIFY_SLACK_CHANNEL="${VORTEX_NOTIFY_SLACK_CHANNEL:-}"
export VORTEX_NOTIFY_SLACK_USERNAME="${VORTEX_NOTIFY_SLACK_USERNAME:-Deployment Bot}"
export VORTEX_NOTIFY_SLACK_ICON_EMOJI="${VORTEX_NOTIFY_SLACK_ICON_EMOJI:-:rocket:}"

# Scenario 1: Branch pre-deployment
echo "==> Scenario 1: Branch pre-deployment"
export VORTEX_NOTIFY_PROJECT="Test Project"
export VORTEX_NOTIFY_BRANCH="main"
export VORTEX_NOTIFY_SHA="abc123def456"
export VORTEX_NOTIFY_LABEL="main"
export VORTEX_NOTIFY_PR_NUMBER=""
export VORTEX_NOTIFY_EVENT="pre_deployment"
./scripts/vortex/notify.sh
echo ""

# Scenario 2: Branch post-deployment
echo "==> Scenario 2: Branch post-deployment"
export VORTEX_NOTIFY_PROJECT="Test Project"
export VORTEX_NOTIFY_BRANCH="main"
export VORTEX_NOTIFY_SHA="abc123def456"
export VORTEX_NOTIFY_LABEL="main"
export VORTEX_NOTIFY_PR_NUMBER=""
export VORTEX_NOTIFY_EVENT="post_deployment"
./scripts/vortex/notify.sh
echo ""

# Scenario 3: PR pre-deployment
echo "==> Scenario 3: PR pre-deployment"
export VORTEX_NOTIFY_PROJECT="Test Project with PR"
export VORTEX_NOTIFY_BRANCH="feature/PROJ-123-test-feature"
export VORTEX_NOTIFY_SHA="abc123def456"
export VORTEX_NOTIFY_LABEL="PR-123"
export VORTEX_NOTIFY_PR_NUMBER="123"
export VORTEX_NOTIFY_EVENT="pre_deployment"
./scripts/vortex/notify.sh
echo ""

# Scenario 4: PR post-deployment
echo "==> Scenario 4: PR post-deployment"
export VORTEX_NOTIFY_PROJECT="Test Project with PR"
export VORTEX_NOTIFY_BRANCH="feature/PROJ-123-test-feature"
export VORTEX_NOTIFY_SHA="abc123def456"
export VORTEX_NOTIFY_LABEL="PR-123"
export VORTEX_NOTIFY_PR_NUMBER="123"
export VORTEX_NOTIFY_EVENT="post_deployment"
./scripts/vortex/notify.sh
echo ""

echo "All 4 notifications sent! Check your Slack channel."
