#!/usr/bin/env bash
##
# Notification dispatch to Jira.
#
# Features:
# - posts comment with a URL of a deployment environment
# - moves an issues to a state
# - assigns an issue to a dedicated user
#
# Uses user's token to perform operations.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# JIRA user.
VORTEX_NOTIFY_JIRA_USER="${VORTEX_NOTIFY_JIRA_USER:-}"

# JIRA token.
VORTEX_NOTIFY_JIRA_TOKEN="${VORTEX_NOTIFY_JIRA_TOKEN:-}"

# Deployment reference, such as a git SHA.
VORTEX_NOTIFY_BRANCH="${VORTEX_NOTIFY_BRANCH:-}"

# Deployment environment URL.
VORTEX_NOTIFY_ENVIRONMENT_URL="${VORTEX_NOTIFY_ENVIRONMENT_URL:-}"

# Deployment comment prefix.
VORTEX_NOTIFY_JIRA_COMMENT_PREFIX="${VORTEX_NOTIFY_JIRA_COMMENT_PREFIX:-"Deployed to "}"

# State to move the ticket to.
#
# If left empty - no transition will be performed.
VORTEX_NOTIFY_JIRA_TRANSITION="${VORTEX_NOTIFY_JIRA_TRANSITION:-}"

# Assign the ticket to this account.
#
# If left empty - no assignment will be performed.
VORTEX_NOTIFY_JIRA_ASSIGNEE="${VORTEX_NOTIFY_JIRA_ASSIGNEE:-}"

# JIRA API endpoint.
VORTEX_NOTIFY_JIRA_ENDPOINT="${VORTEX_NOTIFY_JIRA_ENDPOINT:-https://jira.atlassian.com}"

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

[ -z "${VORTEX_NOTIFY_JIRA_USER}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_USER" && exit 1
[ -z "${VORTEX_NOTIFY_JIRA_TOKEN}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_TOKEN" && exit 1
[ -z "${VORTEX_NOTIFY_BRANCH}" ] && fail "Missing required value for VORTEX_NOTIFY_BRANCH" && exit 1

info "Started JIRA notification."

#
# Function to extract last value from JSON object passed via STDIN.
#
extract_json_first_value() {
  local key="${1}"
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); \$first=reset(\$data); isset(\$first[\"${key}\"]) ? print trim(json_encode(\$first[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value() {
  local key="${1}"
  php -r "\$data=json_decode(file_get_contents('php://stdin'), TRUE); isset(\$data[\"${key}\"]) ? print trim(json_encode(\$data[\"${key}\"], JSON_UNESCAPED_SLASHES), '\"') : exit(1);"
}

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value_by_value() {
  local key="${1}"
  local value="${2}"
  local return_key="${3}"

  php -r "\$key=\"${key}\"; \$value=\"${value}\"; \$return_key=\"${return_key}\";\$data = json_decode(file_get_contents('php://stdin'), TRUE);\$result = array_filter(\$data, function (\$object) use (\$key, \$value, \$return_key) {return !empty(\$object[\$key]) && \$object[\$key] == \$value;});\$result=reset(\$result);print trim(json_encode((\$result[\$return_key] ?? ''), JSON_UNESCAPED_SLASHES), '\"');"
}

#
# Extract issue ID from the branch.
#
extract_issue() {
  echo "${1}" | sed -nE "s/([^\/]+\/)?([A-Za-z0-9]+\-[0-9]+).*/\2/p"
}

note "Extracting issue"
issue="$(extract_issue "${VORTEX_NOTIFY_BRANCH}")"
[ "${issue}" = "" ] && pass "Branch ${VORTEX_NOTIFY_BRANCH} does not contain issue number." && exit 0
note "Found issue ${issue}."

note "Creating API token"
base64_opts=() && [ "$(uname)" != "Darwin" ] && base64_opts=(-w 0)
token="$(echo -n "${VORTEX_NOTIFY_JIRA_USER}:${VORTEX_NOTIFY_JIRA_TOKEN}" | base64 "${base64_opts[@]}")"

echo -n "       Checking API access..."
payload="$(curl \
  -s \
  -X GET \
  -H "Authorization: Basic ${token}" \
  -H "Content-Type: application/json" \
  "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/myself")"

account_id="$(echo "${payload}" | extract_json_value "accountId" || echo "error")"

[ "${#account_id}" -lt 24 ] && fail "Unable to authenticate" && echo "${payload}" && exit 1
echo "success"

if [ -n "${VORTEX_NOTIFY_JIRA_COMMENT_PREFIX}" ]; then
  note "Posting a comment."

  [ -z "${VORTEX_NOTIFY_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_ENVIRONMENT_URL." && exit 1

  comment="[{\"type\": \"text\",\"text\": \"${VORTEX_NOTIFY_JIRA_COMMENT_PREFIX}\"},{\"type\": \"inlineCard\",\"attrs\": {\"url\": \"${VORTEX_NOTIFY_ENVIRONMENT_URL}\"}}]"
  payload="$(curl \
    -s \
    -X POST \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/comment" \
    --data "{\"body\": {\"type\": \"doc\", \"version\": 1, \"content\": [{\"type\": \"paragraph\", \"content\": ${comment}}]}}")"

  comment_id="$(echo "${payload}" | extract_json_value "id" || echo "error")"
  [ "$(expr "x${comment_id}" : "x[0-9]*$")" -eq 0 ] && fail "Unable to create a comment" && exit 1

  pass "Posted comment with ID ${comment_id}."
fi

if [ -n "${VORTEX_NOTIFY_JIRA_TRANSITION}" ]; then
  note "Transitioning issue to ${VORTEX_NOTIFY_JIRA_TRANSITION}"

  echo -n "       Discovering transition ID for ${VORTEX_NOTIFY_JIRA_TRANSITION}..."
  payload="$(curl \
    -s \
    -X GET \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/transitions")"

  transition_id="$(echo "${payload}" | extract_json_value "transitions" | extract_json_value_by_value "name" "${VORTEX_NOTIFY_JIRA_TRANSITION}" "id" || echo "error")"
  { [ "${transition_id}" = "" ] || [ "$(expr "x${transition_id}" : "x[0-9]*$")" -eq 0 ]; } && fail "Unable to retrieve transition ID" && exit 1
  echo "success"

  payload="$(curl \
    -s \
    -X POST \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/transitions" \
    --data "{ \"transition\": {\"id\": \"${transition_id}\"}}")"

  pass "Transitioned issue to ${VORTEX_NOTIFY_JIRA_TRANSITION} "
fi

if [ -n "${VORTEX_NOTIFY_JIRA_ASSIGNEE:-}" ]; then
  note "Assigning issue to ${VORTEX_NOTIFY_JIRA_ASSIGNEE}"

  echo -n "       Discovering user ID for ${VORTEX_NOTIFY_JIRA_ASSIGNEE}..."
  payload="$(curl \
    -s \
    -X GET \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/user/assignable/search?query=${VORTEX_NOTIFY_JIRA_ASSIGNEE}&issueKey=${issue}")"

  account_id="$(echo "${payload}" | extract_json_first_value "accountId" || echo "error")"
  [ "${#account_id}" -lt 24 ] && fail "Unable to retrieve assignee account ID" && echo "${payload}" && exit 1
  echo "success"

  payload="$(curl \
    -s \
    -X PUT \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/assignee" \
    --data "{ \"accountId\": \"${account_id}\"}")"

  pass "Assigned issue to ${VORTEX_NOTIFY_JIRA_ASSIGNEE}"
fi

pass "Finished JIRA notification."
