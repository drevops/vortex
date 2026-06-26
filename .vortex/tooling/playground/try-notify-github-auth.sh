#!/usr/bin/env bash
##
# Manual test script for GitHub authentication.
#
# Usage:
#   export GITHUB_TOKEN="your-github-token"
#   ./try-notify-github-auth.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Default values for testing (requires GITHUB_TOKEN to be set externally)
GITHUB_TOKEN="${GITHUB_TOKEN:-}"

# Check if GITHUB_TOKEN is set
if [ -z "${GITHUB_TOKEN}" ]; then
  echo "Error: GITHUB_TOKEN environment variable is required"
  echo "Usage: export GITHUB_TOKEN=\"your-token\" && $0"
  exit 1
fi

echo "Testing GitHub authentication..."
echo "Token: [set]"
echo ""

echo "Testing API authentication (/user endpoint):"
RESPONSE=$(curl -s -w $'\n%{http_code}' \
  -H "Authorization: token ${GITHUB_TOKEN}" \
  -H "Accept: application/vnd.github.v3+json" \
  "https://api.github.com/user")
HTTP_CODE=$(printf '%s' "${RESPONSE}" | tail -n1)
RESPONSE_BODY=$(printf '%s' "${RESPONSE}" | sed '$d')

echo "HTTP Status Code: ${HTTP_CODE}"
if [ "${HTTP_CODE}" = "200" ]; then
  echo "✅ Authentication successful!"
  echo ""
  echo "User details:"
  printf '%s' "${RESPONSE_BODY}" | python3 -c "import sys, json; user = json.load(sys.stdin); print(f\"  Login: {user.get('login')}\"); print(f\"  Name: {user.get('name')}\"); print(f\"  Email: {user.get('email')}\")" 2>/dev/null || printf '%s\n' "${RESPONSE_BODY}"
else
  echo "❌ Authentication failed"
  echo "Response:"
  printf '%s\n' "${RESPONSE_BODY}"
  echo ""
  echo "Troubleshooting:"
  echo "- Verify you're using a personal access token (classic or fine-grained)"
  echo "- Check the token hasn't expired or been revoked"
  echo "- Ensure the token has required scopes (repo, workflow for deployments)"
  echo "- Generate a new token at: https://github.com/settings/tokens"
fi
echo ""
