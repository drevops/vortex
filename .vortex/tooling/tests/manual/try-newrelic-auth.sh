#!/usr/bin/env bash
##
# Manual test script for New Relic authentication.
#
# Usage:
#   export NEWRELIC_USER_KEY="your-user-api-key"
#   export NEWRELIC_ENDPOINT="https://api.newrelic.com/v2"  # Optional
#   ./try-newrelic-auth.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Default values for testing (requires NEWRELIC_USER_KEY to be set externally)
NEWRELIC_USER_KEY="${NEWRELIC_USER_KEY:-}"
NEWRELIC_ENDPOINT="${NEWRELIC_ENDPOINT:-https://api.newrelic.com/v2}"

# Check if NEWRELIC_USER_KEY is set
if [ -z "${NEWRELIC_USER_KEY}" ]; then
  echo "Error: NEWRELIC_USER_KEY environment variable is required"
  echo "Usage: export NEWRELIC_USER_KEY=\"your-user-api-key\" && $0"
  exit 1
fi

echo "Testing New Relic authentication..."
echo "Endpoint: ${NEWRELIC_ENDPOINT}"
echo "User API Key: ${NEWRELIC_USER_KEY:0:20}..."
echo ""

echo "Testing API key validity (/applications.json endpoint):"
HTTP_CODE=$(curl -w "%{http_code}" -o /tmp/newrelic_response.txt -s -X GET \
  -H "Api-Key: ${NEWRELIC_USER_KEY}" \
  "${NEWRELIC_ENDPOINT}/applications.json")

echo "HTTP Status Code: ${HTTP_CODE}"
if [ "${HTTP_CODE}" = "200" ]; then
  echo "✅ Authentication successful!"
  echo ""
  echo "Available applications:"
  cat /tmp/newrelic_response.txt | python3 -c "import sys, json; apps = json.load(sys.stdin).get('applications', []); print('Found {} applications:'.format(len(apps))); [print('  - {} (ID: {})'.format(app.get('name'), app.get('id'))) for app in apps[:10]]" 2>/dev/null || cat /tmp/newrelic_response.txt
else
  echo "❌ Authentication failed"
  echo "Response:"
  cat /tmp/newrelic_response.txt
  echo ""
  echo "Troubleshooting:"
  echo "- Verify you're using a REST API key (User API key)"
  echo "- Check the key hasn't expired or been revoked"
  echo "- Ensure the key has proper permissions"
  echo "- Get your API key from: https://one.newrelic.com/launcher/api-keys-ui.api-keys-launcher"
fi
echo ""

# Clean up
rm -f /tmp/newrelic_response.txt
