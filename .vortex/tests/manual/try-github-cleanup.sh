#!/usr/bin/env bash
##
# Manual cleanup script for GitHub deployments.
#
# Marks all deployments as inactive (effectively removing them from active list).
# Tests against drevops/vortex-destination-gha repository.
#
# Usage:
#   export GITHUB_TOKEN="your-github-token"
#   ./try-github-cleanup.sh

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source secrets if available
if [ -f "$HOME/.profile.secrets" ]; then
  # shellcheck disable=SC1091
  . "$HOME/.profile.secrets"
fi

# Hardcoded test repository
GITHUB_REPOSITORY="drevops/vortex-destination-gha"

# Default values for testing (requires GITHUB_TOKEN to be set externally)
GITHUB_TOKEN="${GITHUB_TOKEN:-}"

# Check if GITHUB_TOKEN is set
if [ -z "${GITHUB_TOKEN}" ]; then
  echo "Error: GITHUB_TOKEN environment variable is required"
  echo "Usage: export GITHUB_TOKEN=\"your-token\" && $0"
  exit 1
fi

echo "GitHub Deployment Cleanup"
echo "========================="
echo ""
echo "Repository: ${GITHUB_REPOSITORY}"
echo "Token: ${GITHUB_TOKEN:0:20}..."
echo ""

# Function to extract JSON value
extract_json_value() {
  local key=${1}
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

echo "Fetching all deployments..."
deployments=$(curl -s \
  -H "Authorization: token ${GITHUB_TOKEN}" \
  -H "Accept: application/vnd.github.v3+json" \
  "https://api.github.com/repos/${GITHUB_REPOSITORY}/deployments")

# Count deployments
deployment_count=$(echo "${deployments}" | php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); echo count(\$data);")

if [ "${deployment_count}" = "0" ]; then
  echo "No deployments found."
  exit 0
fi

echo "Found ${deployment_count} deployment(s)."
echo ""

# Parse deployment IDs
deployment_ids=$(echo "${deployments}" | php -r "
  \$data = json_decode(file_get_contents('php://stdin'), TRUE);
  foreach (\$data as \$deployment) {
    echo \$deployment['id'] . \"\\n\";
  }
")

# Mark each deployment as inactive
inactive_count=0
deleted_count=0
for deployment_id in ${deployment_ids}; do
  echo "Marking deployment ${deployment_id} as inactive..."

  response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
    -X POST \
    -H "Authorization: token ${GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    "https://api.github.com/repos/${GITHUB_REPOSITORY}/deployments/${deployment_id}/statuses" \
    -d '{"state":"inactive"}')

  http_code=$(echo "${response}" | grep "HTTP_CODE:" | cut -d: -f2)

  if [ "${http_code}" = "201" ]; then
    echo "  ✅ Deployment ${deployment_id} marked as inactive"
    inactive_count=$((inactive_count + 1))

    # Now delete the inactive deployment
    echo "  Deleting deployment ${deployment_id}..."
    delete_response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
      -X DELETE \
      -H "Authorization: token ${GITHUB_TOKEN}" \
      -H "Accept: application/vnd.github+json" \
      -H "X-GitHub-Api-Version: 2022-11-28" \
      "https://api.github.com/repos/${GITHUB_REPOSITORY}/deployments/${deployment_id}")

    delete_http_code=$(echo "${delete_response}" | grep "HTTP_CODE:" | cut -d: -f2)

    if [ "${delete_http_code}" = "204" ]; then
      echo "  ✅ Deployment ${deployment_id} deleted successfully"
      deleted_count=$((deleted_count + 1))
    else
      echo "  ❌ Failed to delete deployment ${deployment_id} (HTTP ${delete_http_code})"
      echo "${delete_response}" | grep -v "HTTP_CODE:"
    fi
  else
    echo "  ❌ Failed to mark deployment ${deployment_id} as inactive (HTTP ${http_code})"
  fi
done

echo ""
echo "Cleanup complete!"
echo "Marked ${inactive_count} of ${deployment_count} deployment(s) as inactive."
echo "Deleted ${deleted_count} of ${deployment_count} deployment(s) permanently."
echo ""
echo "Check deployments at: https://github.com/${GITHUB_REPOSITORY}/deployments"
