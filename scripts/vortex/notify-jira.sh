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

# JIRA notification project name.
VORTEX_NOTIFY_JIRA_PROJECT="${VORTEX_NOTIFY_JIRA_PROJECT:-${VORTEX_NOTIFY_PROJECT:-}}"

# JIRA notification user email address.
VORTEX_NOTIFY_JIRA_USER_EMAIL="${VORTEX_NOTIFY_JIRA_USER_EMAIL:-}"

# JIRA notification API token.
#
# @see https://www.vortextemplate.com/docs/deployment/notifications#jira
VORTEX_NOTIFY_JIRA_TOKEN="${VORTEX_NOTIFY_JIRA_TOKEN:-}"

# JIRA notification git branch name (used for issue extraction).
VORTEX_NOTIFY_JIRA_BRANCH="${VORTEX_NOTIFY_JIRA_BRANCH:-${VORTEX_NOTIFY_BRANCH:-}}"

# JIRA notification deployment label (human-readable identifier for display).
VORTEX_NOTIFY_JIRA_LABEL="${VORTEX_NOTIFY_JIRA_LABEL:-${VORTEX_NOTIFY_LABEL:-}}"

# JIRA notification environment URL.
VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL="${VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL:-${VORTEX_NOTIFY_ENVIRONMENT_URL:-}}"

# JIRA notification login URL.
VORTEX_NOTIFY_JIRA_LOGIN_URL="${VORTEX_NOTIFY_JIRA_LOGIN_URL:-${VORTEX_NOTIFY_LOGIN_URL:-}}"

# JIRA notification message template (will be converted to ADF format).
# Available tokens: %project%, %label%, %timestamp%, %environment_url%, %login_url%
VORTEX_NOTIFY_JIRA_MESSAGE="${VORTEX_NOTIFY_JIRA_MESSAGE:-}"

# JIRA notification event type. Can be 'pre_deployment' or 'post_deployment'.
VORTEX_NOTIFY_JIRA_EVENT="${VORTEX_NOTIFY_JIRA_EVENT:-${VORTEX_NOTIFY_EVENT:-post_deployment}}"

# JIRA notification state to transition to.
#
# If left empty - no transition will be performed.
VORTEX_NOTIFY_JIRA_TRANSITION="${VORTEX_NOTIFY_JIRA_TRANSITION:-}"

# JIRA notification assignee email address.
#
# If left empty - no assignment will be performed.
VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL="${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL:-}"

# JIRA notification API endpoint.
VORTEX_NOTIFY_JIRA_ENDPOINT="${VORTEX_NOTIFY_JIRA_ENDPOINT:-https://jira.atlassian.com}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in php curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${VORTEX_NOTIFY_JIRA_USER_EMAIL}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_USER_EMAIL" && exit 1
[ -z "${VORTEX_NOTIFY_JIRA_TOKEN}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_TOKEN" && exit 1
[ -z "${VORTEX_NOTIFY_JIRA_LABEL}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_LABEL" && exit 1
[ -z "${VORTEX_NOTIFY_JIRA_PROJECT}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_PROJECT" && exit 1

info "Started JIRA notification."

# Skip if this is a pre-deployment event (JIRA only processes post-deployment).
if [ "${VORTEX_NOTIFY_JIRA_EVENT}" = "pre_deployment" ]; then
  pass "Skipping JIRA notification for pre_deployment event."
  exit 0
fi

# Set default message template if not provided.
if [ -z "${VORTEX_NOTIFY_JIRA_MESSAGE}" ]; then
  VORTEX_NOTIFY_JIRA_MESSAGE="## This is an automated message ##

Site %project% %label% has been deployed at %timestamp% and is available at %environment_url%.

Login at: %login_url%"
fi

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
# Extract issue ID from deployment label.
#
extract_issue() {
  echo "${1}" | sed -nE "s/([^\/]+\/)?([A-Za-z0-9]+\-[0-9]+).*/\2/p"
}

task "Extracting issue"
issue="$(extract_issue "${VORTEX_NOTIFY_JIRA_BRANCH}")"
[ "${issue}" = "" ] && pass "Branch ${VORTEX_NOTIFY_JIRA_BRANCH} does not contain issue number." && exit 0
note "Found issue ${issue}."

task "Creating API token"
base64_opts=() && [ "$(uname)" != "Darwin" ] && base64_opts=(-w 0)
token="$(echo -n "${VORTEX_NOTIFY_JIRA_USER_EMAIL}:${VORTEX_NOTIFY_JIRA_TOKEN}" | base64 "${base64_opts[@]}")"

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

task "Posting a comment."

[ -z "${VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL." && exit 1
[ -z "${VORTEX_NOTIFY_JIRA_LOGIN_URL}" ] && fail "Missing required value for VORTEX_NOTIFY_JIRA_LOGIN_URL." && exit 1

# Generate timestamp.
timestamp=$(date '+%d/%m/%Y %H:%M:%S %Z')

# Build message by replacing tokens.
message="${VORTEX_NOTIFY_JIRA_MESSAGE}"
message=$(REPLACEMENT="${VORTEX_NOTIFY_JIRA_PROJECT}" TEMPLATE="${message}" php -r 'echo str_replace("%project%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
message=$(REPLACEMENT="${VORTEX_NOTIFY_JIRA_LABEL}" TEMPLATE="${message}" php -r 'echo str_replace("%label%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
message=$(REPLACEMENT="${timestamp}" TEMPLATE="${message}" php -r 'echo str_replace("%timestamp%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
message=$(REPLACEMENT="${VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL}" TEMPLATE="${message}" php -r 'echo str_replace("%environment_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
message=$(REPLACEMENT="${VORTEX_NOTIFY_JIRA_LOGIN_URL}" TEMPLATE="${message}" php -r 'echo str_replace("%login_url%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')

# Build JIRA Atlassian Document Format (ADF) from the message using PHP with proper escaping.
# shellcheck disable=SC2016
comment_body=$(VORTEX_NOTIFY_JIRA_PROJECT="${VORTEX_NOTIFY_JIRA_PROJECT}" VORTEX_NOTIFY_JIRA_LABEL="${VORTEX_NOTIFY_JIRA_LABEL}" timestamp="${timestamp}" VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL="${VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL}" VORTEX_NOTIFY_JIRA_LOGIN_URL="${VORTEX_NOTIFY_JIRA_LOGIN_URL}" php -r '
$project = getenv("VORTEX_NOTIFY_JIRA_PROJECT");
$label = getenv("VORTEX_NOTIFY_JIRA_LABEL");
$timestamp = getenv("timestamp");
$envUrl = getenv("VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL");
$loginUrl = getenv("VORTEX_NOTIFY_JIRA_LOGIN_URL");

$content = [[
  "type" => "paragraph",
  "content" => [
    ["type" => "text", "text" => "## This is an automated message ##"],
    ["type" => "hardBreak"],
    ["type" => "hardBreak"],
    ["type" => "text", "text" => "Site " . $project . " "],
    ["type" => "text", "text" => $label, "marks" => [["type" => "code"]]],
    ["type" => "text", "text" => " has been deployed at " . $timestamp . " and is available at "],
    ["type" => "text", "text" => $envUrl, "marks" => [["type" => "link", "attrs" => ["href" => $envUrl]]]],
    ["type" => "text", "text" => "."],
    ["type" => "hardBreak"],
    ["type" => "hardBreak"],
    ["type" => "text", "text" => "Login at: "],
    ["type" => "text", "text" => $loginUrl, "marks" => [["type" => "link", "attrs" => ["href" => $loginUrl]]]]
  ]
]];

$body = [
  "type" => "doc",
  "version" => 1,
  "content" => $content
];

echo json_encode($body, JSON_UNESCAPED_SLASHES);
')

info "JIRA notification summary:"
note "Project        : ${VORTEX_NOTIFY_JIRA_PROJECT}"
note "Deployment     : ${VORTEX_NOTIFY_JIRA_LABEL}"
note "Issue          : ${issue}"
note "Environment URL: ${VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL}"
note "Login URL      : ${VORTEX_NOTIFY_JIRA_LOGIN_URL}"
note "Endpoint       : ${VORTEX_NOTIFY_JIRA_ENDPOINT}"
note "User email     : ${VORTEX_NOTIFY_JIRA_USER_EMAIL}"
note "Transition     : ${VORTEX_NOTIFY_JIRA_TRANSITION:-<none>}"
note "Assignee email : ${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL:-<none>}"

payload="$(curl \
  -s \
  -X POST \
  -H "Authorization: Basic ${token}" \
  -H "Content-Type: application/json" \
  --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/comment" \
  --data "{\"body\": ${comment_body}}")"

comment_id="$(echo "${payload}" | extract_json_value "id" || echo "error")"
if [ "$(expr "x${comment_id}" : "x[0-9]*$")" -eq 0 ]; then
  fail "Unable to create a comment"
  echo "API Response: ${payload}"
  exit 1
fi

pass "Posted comment with ID ${comment_id}."

if [ -n "${VORTEX_NOTIFY_JIRA_TRANSITION}" ]; then
  task "Transitioning issue to ${VORTEX_NOTIFY_JIRA_TRANSITION}"

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

if [ -n "${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL:-}" ]; then
  task "Assigning issue to ${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL}"

  echo -n "       Discovering user ID for ${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL}..."
  payload="$(curl \
    -s \
    -X GET \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${VORTEX_NOTIFY_JIRA_ENDPOINT}/rest/api/3/user/assignable/search?query=${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL}&issueKey=${issue}")"

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

  pass "Assigned issue to ${VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL}"
fi

pass "Finished JIRA notification."
