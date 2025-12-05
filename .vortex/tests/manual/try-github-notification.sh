#!/usr/bin/env bash
##
# Manual test script for GitHub notifications.
#
# Tests against PR #2 in drevops/vortex-destination-gha (kept open for testing).
#
# Usage:
#   export GITHUB_TOKEN="your-github-token"
#   ./try-github-notification.sh [pre|post]

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

# Hardcoded test repository and PR
GITHUB_REPOSITORY="drevops/vortex-destination-gha"
PR_NUMBER="2"

# Default values for testing (requires GITHUB_TOKEN to be set externally)
GITHUB_TOKEN="${GITHUB_TOKEN:-}"

# Check if GITHUB_TOKEN is set
if [ -z "${GITHUB_TOKEN}" ]; then
  echo "Error: GITHUB_TOKEN environment variable is required"
  echo "Usage: export GITHUB_TOKEN=\"your-token\" && $0"
  exit 1
fi

echo "Testing GitHub notification..."
echo ""
echo "Repository: ${GITHUB_REPOSITORY}"
echo "PR Number : ${PR_NUMBER}"
echo "Token: ${GITHUB_TOKEN:0:20}..."
echo ""

# Determine test scenario
SCENARIO="${1:-pre}"

if [ "${SCENARIO}" = "post" ]; then
  echo "Testing post-deployment notification (updates deployment status)"
  export VORTEX_NOTIFY_EVENT="post_deployment"
else
  echo "Testing pre-deployment notification (creates deployment)"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
fi

# Set deployment context variables
export VORTEX_NOTIFY_PROJECT="Test Project"
export VORTEX_NOTIFY_BRANCH="feature/test-github-notification-do-not-merge"
export VORTEX_NOTIFY_SHA="abc123def456"
export VORTEX_NOTIFY_PR_NUMBER="${PR_NUMBER}"
export VORTEX_NOTIFY_LABEL="PR-${PR_NUMBER}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="https://pr-${PR_NUMBER}.example.com"

# Set required environment variables
export VORTEX_NOTIFY_CHANNELS=github
export VORTEX_NOTIFY_GITHUB_TOKEN="${GITHUB_TOKEN}"
export VORTEX_NOTIFY_GITHUB_REPOSITORY="${GITHUB_REPOSITORY}"

# Optional: Customize environment type
export VORTEX_NOTIFY_GITHUB_ENVIRONMENT_TYPE="${VORTEX_NOTIFY_GITHUB_ENVIRONMENT_TYPE:-PR}"

echo "Running notification script..."
echo ""

# Change to project root and run the notification
cd "${PROJECT_ROOT}" || exit 1
./scripts/vortex/notify.sh

echo ""
if [ "${SCENARIO}" = "post" ]; then
  echo "Check GitHub PR for the updated deployment status (should show as success):"
  echo "https://github.com/${GITHUB_REPOSITORY}/pull/${PR_NUMBER}"
else
  echo "Check GitHub PR for the new deployment created:"
  echo "https://github.com/${GITHUB_REPOSITORY}/pull/${PR_NUMBER}"
  echo ""
  echo "Run with 'post' argument to mark deployment as successful:"
  echo "  $0 post"
fi
echo ""
echo "Deployments page:"
echo "https://github.com/${GITHUB_REPOSITORY}/deployments"
