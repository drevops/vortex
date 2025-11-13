#!/usr/bin/env bats
##
# Unit tests for JIRA notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: jira" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  account_id="123456789020165700ede21g"
  assignee_account_id="987654321c20165700ede21g"
  comment_id="1234"

  # shellcheck disable=SC2034
  declare -a STEPS=(
    "Started dispatching notifications."
    "Started JIRA notification."
    "Extracting issue"
    "Found issue proj-1234."
    "- Branch feature/proj-1234-some-description does not contain issue number."
    "Checking API access."
    "Creating API token"
    "@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json https://jira.atlassian.com/rest/api/3/myself # {\"accountId\": \"${account_id}\", \"othervar\": \"54321\"}"
    "Posting a comment."
    "@curl -s -X POST -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/comment --data {\"body\": {\"type\": \"doc\", \"version\": 1, \"content\": [{\"type\": \"paragraph\", \"content\": [{\"type\": \"text\",\"text\": \"Deployed to \"},{\"type\": \"inlineCard\",\"attrs\": {\"url\": \"https://develop.testproject.com\"}}]}]}} # {\"id\": \"${comment_id}\", \"othervar\": \"54321\"}"
    "Posted comment with ID ${comment_id}."
    "Transitioning issue to QA"
    "Discovering transition ID for QA"
    '@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/transitions # {"expand":"transitions","transitions":[{"id":"123","name":"QA"},{"id":"456","name":"Closed"}]}'
    '@curl -s -X POST -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/transitions --data { "transition": {"id": "123"}} # '
    "Transitioned issue to QA"
    "Assigning issue to jane.doe@example.com"
    "Discovering user ID for jane.doe@example.com"
    "@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/user/assignable/search?query=jane.doe@example.com&issueKey=proj-1234 # [{\"accountId\": \"${assignee_account_id}\", \"othervar\": \"54321\"}, {\"accountId\": \"01987654321c20165700edeg\", \"othervar\": \"54321\"}]"
    '@curl -s -X PUT -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/assignee --data { "accountId": "987654321c20165700ede21g"} # '
  )

  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="jira"
  export VORTEX_NOTIFY_JIRA_USER="john.doe@example.com"
  export VORTEX_NOTIFY_JIRA_TOKEN="token12345"
  export VORTEX_NOTIFY_BRANCH="feature/proj-1234-some-description"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export VORTEX_NOTIFY_JIRA_TRANSITION="QA"
  export VORTEX_NOTIFY_JIRA_ASSIGNEE="jane.doe@example.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: jira, pre_deployment skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_NOTIFY_CHANNELS="jira"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_JIRA_USER="john.doe@example.com"
  export VORTEX_NOTIFY_JIRA_TOKEN="token12345"
  export VORTEX_NOTIFY_BRANCH="feature/proj-1234-some-description"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Started JIRA notification."
  assert_output_contains "Skipping JIRA notification for pre_deployment event."
  assert_output_not_contains "Extracting issue"
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}
