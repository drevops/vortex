#!/usr/bin/env bash
##
# Manual test script for JIRA authentication.
#
# Usage:
#   export JIRA_USER="your-email@example.com"
#   export JIRA_TOKEN="your-api-token"
#   export JIRA_ENDPOINT="https://your-domain.atlassian.net"
#   ./try-jira-auth.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Default values for testing (requires JIRA_TOKEN to be set externally)
JIRA_USER="${JIRA_USER:-alex@drevops.com}"
JIRA_TOKEN="${JIRA_TOKEN:-}"
JIRA_ENDPOINT="${JIRA_ENDPOINT:-https://drevops.atlassian.net}"

# Check if JIRA_TOKEN is set
if [ -z "${JIRA_TOKEN}" ]; then
  echo "Error: JIRA_TOKEN environment variable is required"
  echo "Usage: export JIRA_TOKEN=\"your-api-token\" && $0"
  exit 1
fi

echo "Testing JIRA authentication..."
echo "User: ${JIRA_USER}"
echo "Endpoint: ${JIRA_ENDPOINT}"
echo ""

# Handle base64 encoding across platforms
# Use printf instead of echo -n for better portability
TOKEN=$(printf "%s:%s" "${JIRA_USER}" "${JIRA_TOKEN}" | base64 | tr -d '\n\r ')

echo "Encoded token (first 50 chars): ${TOKEN:0:50}..."
echo "Token length: ${#TOKEN}"
echo ""

# Debug: Show what we're encoding
echo "Debug - encoding string: ${JIRA_USER}:<token>"
echo ""

echo "Testing with Basic auth (/myself endpoint):"
HTTP_CODE=$(curl -w "%{http_code}" -o /tmp/jira_response.txt -s -X GET \
  -H "Authorization: Basic ${TOKEN}" \
  -H "Accept: application/json" \
  "${JIRA_ENDPOINT}/rest/api/3/myself")

echo "HTTP Status Code: ${HTTP_CODE}"
if [ "${HTTP_CODE}" = "200" ]; then
  echo "✅ Authentication successful!"
  echo ""
  echo "User info:"
  cat /tmp/jira_response.txt | python3 -m json.tool 2>/dev/null || cat /tmp/jira_response.txt
else
  echo "❌ Authentication failed"
  echo "Response:"
  cat /tmp/jira_response.txt
fi
echo ""
