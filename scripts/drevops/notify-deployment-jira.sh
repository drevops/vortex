#!/usr/bin/env bash
##
# JIRA deployment notification.
#
# - posts comment with a URL of a deployment environment
# - moves an issues to a state
# - assigns an issue to a dedicated user
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

{ [ "${DREVOPS_NOTIFY_DEPLOYMENT_SKIP}" = "1" ] || [ "${SKIP_NOTIFY_GITHUB_DEPLOYMENT}" = "1" ]; } && echo "Skipping notification of GitHub deployment." && exit 0

# JIRA user.
DREVOPS_NOTIFY_DEPLOY_JIRA_USER="${DREVOPS_NOTIFY_DEPLOY_JIRA_USER:-}"

# JIRA token.
DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN="${DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN:-}"

# Deployment reference, such as a git SHA.
DREVOPS_NOTIFY_DEPLOY_BRANCH="${DREVOPS_NOTIFY_DEPLOY_BRANCH:-}"

# Deployment environment URL.
DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL="${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL:-}"

DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE="${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE:-"Deployed to "}"

# State to move the ticket to.
DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION="${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION:-}"

# Assign the ticket to this account.
DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE="${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE:-}"

# JIRA API endpoint.l
DREVOPS_NOTIFY_JIRA_ENDPOINT="${DREVOPS_NOTIFY_JIRA_ENDPOINT:-https://jira.atlassian.com}"

# ------------------------------------------------------------------------------

[ -z "${DREVOPS_NOTIFY_DEPLOY_JIRA_USER}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_JIRA_USER" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_BRANCH}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_BRANCH" && exit 1
[ -z "${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}" ] && [ -z "${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION}" ] && [ -z "${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}" ] && echo "ERROR: At least one of the DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE, DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION or DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE is required" && exit 1

echo "==> Started JIRA deployment notification"

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

#
# Function to extract keyed value from JSON object passed via STDIN.
#
extract_json_value_by_value() {
  local key=$1
  local value=$2
  local return_key=$3

  php -r "\$key=\"${key}\"; \$value=\"${value}\"; \$return_key=\"${return_key}\";\$data = json_decode(file_get_contents('php://stdin'), TRUE);\$result = array_filter(\$data, function (\$object) use (\$key, \$value, \$return_key) {return !empty(\$object[\$key]) && \$object[\$key] == \$value;});\$result=reset(\$result);print trim(json_encode((\$result[\$return_key] ?? ''), JSON_UNESCAPED_SLASHES), '\"');"
}

#
# Extract issue ID from the branch.
#
extract_issue() {
  echo "$1"|sed -nE "s/([^\/]+\/)?([A-Za-z0-9]+\-[0-9]+).*/\2/p"
}

echo "  > Extracting issue"
issue="$(extract_issue "${DREVOPS_NOTIFY_DEPLOY_BRANCH}")"
[ "${issue}" = "" ] && echo "ERROR: Branch ${DREVOPS_NOTIFY_DEPLOY_BRANCH} does not contain issue number." && exit 1
echo "    Found issue ${issue}"

echo "  > Creating API token"
token="$(echo -n "${DREVOPS_NOTIFY_DEPLOY_JIRA_USER}:${DREVOPS_NOTIFY_DEPLOY_JIRA_TOKEN}" | base64 -w 0)"

echo -n "  > Checking API access..."
payload="$(curl \
 -s \
 -X GET \
 -H "Authorization: Basic ${token}" \
 -H "Content-Type: application/json" \
 "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/myself")"

account_id="$(echo "${payload}" | extract_json_value "accountId" || echo "error")"

{ [ "${#account_id}" != "24" ] || [ "$(expr "x$account_id" : "x[0-9a-f]*$")" -eq 0 ]; } \
&& { [ "${#account_id}" != "43" ] || [ "$(expr "x$account_id" : "x[0-9]*:[0-9a-f\-]*$")" -eq 0 ]; } \
&& echo "ERROR: Failed to authenticate" && echo "${payload}" && exit 1
echo "success"

if [ -n "${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}" ]; then
  echo "  > Posting a comment"

  [ -z "${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL}" ] && echo "ERROR: Missing required value for DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL" && exit 1

  comment="[{\"type\": \"text\",\"text\": \"${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}\"},{\"type\": \"inlineCard\",\"attrs\": {\"url\": \"${DREVOPS_NOTIFY_DEPLOY_ENVIRONMENT_URL}\"}}]"
  payload="$(curl \
    -s \
    -X POST \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/comment" \
    --data "{\"body\": {\"type\": \"doc\", \"version\": 1, \"content\": [{\"type\": \"paragraph\", \"content\": ${comment}}]}}")"

  comment_id="$(echo "${payload}" | extract_json_value "id" || echo "error")"
  [ "$(expr "x$comment_id" : "x[0-9]*$")" -eq 0 ] &&  echo "ERROR: Failed to create a comment" && exit 1

  echo "    Successfully posted comment ${comment_id}"
fi

if [ -n "${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION}" ]; then
  echo "  > Changing issue status to ${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION}"

  echo -n "  > Discovering transition ID for ${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION}..."
  payload="$(curl \
    -s \
    -X GET \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/transitions")"

  transition_id="$(echo "${payload}" | extract_json_value "transitions" | extract_json_value_by_value "name" "${DREVOPS_NOTIFY_DEPLOY_JIRA_TRANSITION}" "id" || echo "error")"
  { [ "${transition_id}" = "" ] || [ "$(expr "x$transition_id" : "x[0-9]*$")" -eq 0 ]; } &&  echo "ERROR: Failed to retrieve transition ID" && exit 1
  echo -n "success"

  payload="$(curl \
    -s \
    -X POST \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/transitions" \
    --data "{ \"transition\": {\"id\": \"${transition_id}\"}}")"
fi

if [ -n "${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}" ]; then
  echo "  > Assigning an issue to ${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}"

  echo -n "  > Discovering user ID for ${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}..."
  payload="$(curl \
    -s \
    -X GET \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/user/assignable/search?query=${DREVOPS_NOTIFY_DEPLOY_JIRA_ASSIGNEE}&issueKey=${issue}")"

  account_id="$(echo "${payload}" | extract_json_first_value "accountId" || echo "error")"
  { [ "${#account_id}" != "24" ] || [ "$(expr "x$account_id" : "x[0-9a-f]*$")" -eq 0 ]; } \
    && { [ "${#account_id}" != "43" ] || [ "$(expr "x$account_id" : "x[0-9]*:[0-9a-f\-]*$")" -eq 0 ]; } \
    && echo "ERROR: Failed to retrieve assignee account ID" && echo "${payload}" && exit 1
  echo -n "success"

  payload="$(curl \
    -s \
    -X PUT \
    -H "Authorization: Basic ${token}" \
    -H "Content-Type: application/json" \
    --url "${DREVOPS_NOTIFY_JIRA_ENDPOINT}/rest/api/3/issue/${issue}/assignee" \
    --data "{ \"accountId\": \"${account_id}\"}")"
fi

echo "==> Finished JIRA deployment notification"
