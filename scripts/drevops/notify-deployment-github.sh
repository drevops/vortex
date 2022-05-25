#!/usr/bin/env bash
##
# GitHub deployment notification.
#
# Note: the deployment can be scheduled only if all checks are passed. This
# means that the deployment notification will fail if the CI has not finished
# running and reporting checks as success.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Deployment GitHub token.
DREVOPS_NOTIFY_GITHUB_TOKEN="${DREVOPS_NOTIFY_GITHUB_TOKEN:-${GITHUB_TOKEN}}"

# Deployment repository.
DREVOPS_NOTIFY_DEPLOY_REPOSITORY="${DREVOPS_NOTIFY_DEPLOY_REPOSITORY:-}"

# Deployment reference, such as a git SHA.
DREVOPS_NOTIFY_DEPLOY_REF="${DREVOPS_NOTIFY_DEPLOY_REF:-}"

# Operation type: start or finish.
DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION="${DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION:-}"

# Deployment environment URL.
DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL="${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL:-}"

# Deployment environment type: production, uat, dev, pr.
DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE="${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE:-PR}"

# Flag to skip deployment notification.
DREVOPS_NOTIFY_DEPLOYMENT_SKIP="${DREVOPS_NOTIFY_DEPLOYMENT_SKIP:-}"

# Flag to skip GitHub deployment notification.
DREVOPS_NOTIFY_DEPLOY_GITHUB_SKIP="${DREVOPS_NOTIFY_DEPLOY_GITHUB_SKIP:-}"

# ------------------------------------------------------------------------------

{ [ "${DREVOPS_NOTIFY_DEPLOYMENT_SKIP}" = "1" ] || [ "${DREVOPS_NOTIFY_DEPLOY_GITHUB_SKIP}" = "1" ]; } && echo "Skipping GitHub notification of deployment." && exit 0

[ -z "${DREVOPS_NOTIFY_GITHUB_TOKEN}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_GITHUB_TOKEN" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_REPOSITORY}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_REPOSITORY" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_REF}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_REF" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE" && exit 1

echo "==> Started GitHub deployment notification for operation ${DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION}"

#
# Function to extract last value from JSON object passed via STDIN.
#
extract_json_first_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$first=reset(\$data); isset(\$first[\"${key}\"]) ? print trim(json_encode(\$first[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=$1
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

if [ "${DREVOPS_NOTIFY_DEPLOY_GITHUB_OPERATION}" = "start" ]; then
  payload="$(curl \
    -X POST \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    -s \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_DEPLOY_REPOSITORY}/deployments" \
    -d "{\"ref\":\"${DREVOPS_NOTIFY_DEPLOY_REF}\", \"environment\": \"${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_TYPE}\", \"auto_merge\": false}")"

  deployment_id="$(echo "${payload}" | extract_json_value "id")"

  # Check deployment ID.
  { [ "${#deployment_id}" != "9" ] || [ "$(expr "x$deployment_id" : "x[0-9]*$")" -eq 0 ]; } && echo "ERROR: Failed to get a deployment ID." && exit 1

  echo "  > Marked deployment as started"
else
  [ -z "${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL" && exit 1

  # Returns all deployment for this SHA sorted from the latest to the oldest.
  payload="$(curl \
    -X GET \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    -s \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_DEPLOY_REPOSITORY}/deployments?ref=${DREVOPS_NOTIFY_DEPLOY_REF}")"

  deployment_id="$(echo "${payload}" | extract_json_first_value "id")"

  # Check deployment ID.
  { [ "${#deployment_id}" != "9" ] || [ "$(expr "x$deployment_id" : "x[0-9]*$")" -eq 0 ]; } && echo "ERROR: Failed to get a deployment ID." && exit 1

  # Post status update.
  payload="$(curl \
    -X POST \
    -H "Accept: application/vnd.github.v3+json" \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_DEPLOY_REPOSITORY}/deployments/${deployment_id}/statuses" \
    -s \
    -d "{\"state\":\"success\", \"environment_url\": \"${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL}\"}")"

  status="$(echo "${payload}" | extract_json_value "state")"

  [ "${status}" != "success" ] && echo "ERROR: Unable to set deployment status" && exit 1

  echo "  > Marked deployment as finished"
fi

echo "==> Finished GitHub deployment notification"
