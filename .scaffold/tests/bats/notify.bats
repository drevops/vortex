#!/usr/bin/env bats
##
# Unit tests for notify.sh
#
#shellcheck disable=SC2030,SC2031

load _helper.bash

@test "Notify: skip" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_NOTIFY_SKIP=1
  run ./scripts/drevops/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Skipping dispatching notifications."
  assert_output_not_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: unsupported event" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_NOTIFY_EVENT="customevent"
  run ./scripts/drevops/notify.sh
  assert_failure

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Unsupported event customevent provided."
  assert_output_not_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: custom type" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_NOTIFY_CHANNELS="customtype"
  run ./scripts/drevops/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."
  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: email" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_NOTIFY_CHANNELS="email"
  export DREVOPS_NOTIFY_PROJECT="testproject"
  export DRUPAL_SITE_EMAIL="testproject@example.com"
  export DREVOPS_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe, jane@example.com|Jane Doe"
  export DREVOPS_NOTIFY_REF="develop"
  export DREVOPS_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/drevops/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started email notification."
  assert_output_contains "Notification email(s) sent to: john@example.com, jane@example.com"
  assert_output_contains "Finished email notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: newrelic" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="9876543210"
  mock_curl=$(mock_command "curl")

  mock_set_output "${mock_curl}" "12345678910-1234567890-${app_id}-12345" 1
  mock_set_output "${mock_curl}" "201" 2

  export DREVOPS_NOTIFY_CHANNELS="newrelic"
  export DREVOPS_NOTIFY_PROJECT="testproject"
  export DREVOPS_NOTIFY_NEWRELIC_APIKEY="key1234"
  export DREVOPS_NOTIFY_EMAIL_RECIPIENTS="john@example.com|John Doe,jane@example.com|Jane Doe"
  export DREVOPS_NOTIFY_REF="develop"
  export DREVOPS_NOTIFY_SHA="123456"

  run ./scripts/drevops/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started New Relic notification."

  assert_equal "-s -X GET https://api.newrelic.com/v2/applications.json -H Api-Key:key1234 -s -G -d filter[name]=testproject-develop&exclude_links=true" "$(mock_get_call_args "${mock_curl}" 1)"
  assert_equal '-X POST https://api.newrelic.com/v2/applications/9876543210/deployments.json -L -s -o /dev/null -w %{http_code} -H Api-Key:key1234 -H Content-Type: application/json -d {
  "deployment": {
    "revision": "123456",
    "changelog": "develop deployed",
    "description": "develop deployed",
    "user": "Deployment robot"
  }
}' "$(mock_get_call_args "${mock_curl}" 2)"

  assert_output_contains "Finished New Relic notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: github, pre_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="123456789"

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for pre_deployment event."
    "@curl -X POST -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments -d {\"ref\":\"mybranch\", \"environment\": \"PR\", \"auto_merge\": false} # {\"id\": \"${app_id}\", \"othervar\": \"54321\"}"
    "Marked deployment as started."
    "Finished GitHub notification for pre_deployment event."
    "Finished dispatching notifications."
  )

  mocks="$(run_steps "setup")"

  export DREVOPS_NOTIFY_CHANNELS="github"
  export DREVOPS_NOTIFY_EVENT="pre_deployment"
  export DREVOPS_NOTIFY_GITHUB_TOKEN="token12345"
  export DREVOPS_NOTIFY_REPOSITORY="myorg/myrepo"
  export DREVOPS_NOTIFY_REF="mybranch"
  run ./scripts/drevops/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, post_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="123456789"
  mock_curl=$(mock_command "curl")

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for post_deployment event."
    "@curl -X GET -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments?ref=mybranch # [{\"id\": \"${app_id}\", \"othervar\": \"54321\"},{\"id\": \"98765432101\", \"othervar\": \"12345\"}]"
    "@curl -X POST -H Accept: application/vnd.github.v3+json -H Authorization: token token12345 https://api.github.com/repos/myorg/myrepo/deployments/123456789/statuses -s -d {\"state\":\"success\", \"environment_url\": \"https://develop.testproject.com\"} # {\"state\": \"success\", \"othervar\": \"54321\"}"
    "Marked deployment as finished."
    "Finished GitHub notification for post_deployment event."
    "Finished dispatching notifications."
  )
  mocks="$(run_steps "setup")"

  export DREVOPS_NOTIFY_CHANNELS="github"
  export DREVOPS_NOTIFY_EVENT="post_deployment"
  export DREVOPS_NOTIFY_GITHUB_TOKEN="token12345"
  export DREVOPS_NOTIFY_REPOSITORY="myorg/myrepo"
  export DREVOPS_NOTIFY_REF="mybranch"
  export DREVOPS_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/drevops/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: jira" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  account_id="123456789020165700ede21g"
  assignee_account_id="987654321c20165700ede21g"
  comment_id="1234"

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started JIRA notification."
    "Found issue proj-1234."
    "- Branch feature/proj-1234-some-description does not contain issue number."
    "Checking API access."
    "@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json https://jira.atlassian.com/rest/api/3/myself # {\"accountId\": \"${account_id}\", \"othervar\": \"54321\"}"
    "Posting a comment."
    "@curl -s -X POST -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/comment --data {\"body\": {\"type\": \"doc\", \"version\": 1, \"content\": [{\"type\": \"paragraph\", \"content\": [{\"type\": \"text\",\"text\": \"Deployed to \"},{\"type\": \"inlineCard\",\"attrs\": {\"url\": \"https://develop.testproject.com\"}}]}]}} # {\"id\": \"${comment_id}\", \"othervar\": \"54321\"}"
    "Posted comment with ID ${comment_id}."
    "Transitioning issue to QA"
    "Discovering transition ID for QA"
    "@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/transitions # {\"expand\":\"transitions\",\"transitions\":[{\"id\":\"123\",\"name\":\"QA\"},{\"id\":\"456\",\"name\":\"Closed\"}]}"
    "@curl -s -X POST -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/transitions --data { \"transition\": {\"id\": \"123\"}} # "
    "Transitioned issue to QA"
    "Assigning issue to jane.doe@example.com"
    "Discovering user ID for jane.doe@example.com"
    "@curl -s -X GET -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/user/assignable/search?query=jane.doe@example.com&issueKey=proj-1234 # [{\"accountId\": \"${assignee_account_id}\", \"othervar\": \"54321\"}, {\"accountId\": \"01987654321c20165700edeg\", \"othervar\": \"54321\"}]"
    "@curl -s -X PUT -H Authorization: Basic am9obi5kb2VAZXhhbXBsZS5jb206dG9rZW4xMjM0NQ== -H Content-Type: application/json --url https://jira.atlassian.com/rest/api/3/issue/proj-1234/assignee --data { \"accountId\": \"987654321c20165700ede21g\"} # "
  )

  mocks="$(run_steps "setup")"

  export DREVOPS_NOTIFY_CHANNELS="jira"
  export DREVOPS_NOTIFY_JIRA_USER="john.doe@example.com"
  export DREVOPS_NOTIFY_JIRA_TOKEN="token12345"
  export DREVOPS_NOTIFY_BRANCH="feature/proj-1234-some-description"
  export DREVOPS_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  export DREVOPS_NOTIFY_JIRA_TRANSITION="QA"
  export DREVOPS_NOTIFY_JIRA_ASSIGNEE="jane.doe@example.com"
  run ./scripts/drevops/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: webhook success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1

  export DREVOPS_NOTIFY_CHANNELS="webhook"
  export DREVOPS_NOTIFY_PROJECT="testproject"
  export DREVOPS_NOTIFY_REF="develop"
  export DREVOPS_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"

  export DREVOPS_NOTIFY_WEBHOOK_URL="https://example-webhook-url.com"
  export DREVOPS_NOTIFY_WEBHOOK_METHOD="POST"
  export DREVOPS_NOTIFY_WEBHOOK_HEADERS="Content-type: application/json|Authorization: Bearer API_KEY"
  export DREVOPS_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Test channel 1", "message": "Test channel 1 message"}'

  run ./scripts/drevops/notify.sh
  assert_success

  assert_output_contains "Started dispatching notifications."

  assert_output_contains "Started Webhook notification."

  assert_output_contains "Finished Webhook notification."

  assert_output_contains "Finished dispatching notifications."

  popd >/dev/null || exit 1
}

@test "Notify: webhook failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "400" 1

  export DREVOPS_NOTIFY_CHANNELS="webhook"
  export DREVOPS_NOTIFY_PROJECT="testproject"
  export DREVOPS_NOTIFY_REF="develop"
  export DREVOPS_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"

  export DREVOPS_NOTIFY_WEBHOOK_URL="https://example-webhook-url.com"
  export DREVOPS_NOTIFY_WEBHOOK_METHOD="POST"
  export DREVOPS_NOTIFY_WEBHOOK_HEADERS="Content-type: application/json|Authorization: Bearer API_KEY"
  export DREVOPS_NOTIFY_WEBHOOK_PAYLOAD='{"channel": "Test channel 1", "message": "Test channel 1 message"}'

  run ./scripts/drevops/notify.sh
  assert_failure

  popd >/dev/null || exit 1
}
