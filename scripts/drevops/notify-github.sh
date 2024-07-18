#!/usr/bin/env bash
##
# Notification dispatch to GitHub.
#
# Provides dispatching "deployments" notifications to GitHub.
# @see https://docs.github.com/en/rest/deployments/deployments
#
# GitHub deployments can only be created if all checks pass. Thus, the
# deployment notification dispatch will fail if CI hasn't completed and
# reported successful checks.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Deployment GitHub token.
DREVOPS_NOTIFY_GITHUB_TOKEN="${DREVOPS_NOTIFY_GITHUB_TOKEN:-${GITHUB_TOKEN}}"

# Deployment repository.
DREVOPS_NOTIFY_REPOSITORY="${DREVOPS_NOTIFY_REPOSITORY:-}"

# Deployment reference, such as a git SHA.
DREVOPS_NOTIFY_REF="${DREVOPS_NOTIFY_REF:-}"

# The event to notify about. Can be 'pre_deployment' or 'post_deployment'.
DREVOPS_NOTIFY_EVENT="${DREVOPS_NOTIFY_EVENT:-}"

# Deployment environment URL.
DREVOPS_NOTIFY_ENVIRONMENT_URL="${DREVOPS_NOTIFY_ENVIRONMENT_URL:-}"

# Deployment environment type: production, uat, dev, pr.
DREVOPS_NOTIFY_ENVIRONMENT_TYPE="${DREVOPS_NOTIFY_ENVIRONMENT_TYPE:-PR}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${DREVOPS_NOTIFY_GITHUB_TOKEN}" ] && fail "Missing required value for DREVOPS_NOTIFY_GITHUB_TOKEN" && exit 1
[ -z "${DREVOPS_NOTIFY_REPOSITORY}" ] && fail "Missing required value for DREVOPS_NOTIFY_REPOSITORY" && exit 1
[ -z "${DREVOPS_NOTIFY_REF}" ] && fail "Missing required value for DREVOPS_NOTIFY_REF" && exit 1
[ -z "${DREVOPS_NOTIFY_EVENT}" ] && fail "Missing required value for DREVOPS_NOTIFY_EVENT" && exit 1
[ -z "${DREVOPS_NOTIFY_ENVIRONMENT_TYPE}" ] && fail "Missing required value for DREVOPS_NOTIFY_ENVIRONMENT_TYPE" && exit 1

info "Started GitHub notification for ${DREVOPS_NOTIFY_EVENT} event."

#
# Function to extract last value from JSON object passed via STDIN.
#
extract_json_first_value() {
  local key=${1}
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$first=reset(\$data); isset(\$first[\"${key}\"]) ? print trim(json_encode(\$first[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key=${1}
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

if [ "${DREVOPS_NOTIFY_EVENT}" = "pre_deployment" ]; then
  payload="$(curl \
    -X POST \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    -s \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_REPOSITORY}/deployments" \
    -d "{\"ref\":\"${DREVOPS_NOTIFY_REF}\", \"environment\": \"${DREVOPS_NOTIFY_ENVIRONMENT_TYPE}\", \"auto_merge\": false}")"

  deployment_id="$(echo "${payload}" | extract_json_value "id")"

  # Check deployment ID.
  { [ "${#deployment_id}" -lt 9 ] || [ "${#deployment_id}" -gt 11 ] || [ "$(expr "x${deployment_id}" : "x[0-9]*$")" -eq 0 ]; } && fail "Failed to get a deployment ID for a started operation. Payload: ${payload}" && exit 1

  note "Marked deployment as started."
else
  [ -z "${DREVOPS_NOTIFY_ENVIRONMENT_URL}" ] && fail "Missing required value for DREVOPS_NOTIFY_ENVIRONMENT_URL" && exit 1

  # Returns all deployment for this SHA sorted from the latest to the oldest.
  payload="$(curl \
    -X GET \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github.v3+json" \
    -s \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_REPOSITORY}/deployments?ref=${DREVOPS_NOTIFY_REF}")"

  deployment_id="$(echo "${payload}" | extract_json_first_value "id")"

  # Check deployment ID.
  { [ "${#deployment_id}" -lt 9 ] || [ "${#deployment_id}" -gt 11 ] || [ "$(expr "x${deployment_id}" : "x[0-9]*$")" -eq 0 ]; } && fail "Failed to get a deployment ID for a finished operation. Payload: ${payload}" && exit 1

  # Post status update.
  payload="$(curl \
    -X POST \
    -H "Accept: application/vnd.github.v3+json" \
    -H "Authorization: token ${DREVOPS_NOTIFY_GITHUB_TOKEN}" \
    "https://api.github.com/repos/${DREVOPS_NOTIFY_REPOSITORY}/deployments/${deployment_id}/statuses" \
    -s \
    -d "{\"state\":\"success\", \"environment_url\": \"${DREVOPS_NOTIFY_ENVIRONMENT_URL}\"}")"

  status="$(echo "${payload}" | extract_json_value "state")"

  [ "${status}" != "success" ] && fail "Unable to set deployment status" && exit 1

  note "Marked deployment as finished."
fi

pass "Finished GitHub notification for ${DREVOPS_NOTIFY_EVENT} event."
