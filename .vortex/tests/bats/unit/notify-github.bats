#!/usr/bin/env bats
##
# Unit tests for GitHub notifications (notify.sh).
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "Notify: github, pre_deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="123456789"

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for pre_deployment event."
    "@curl -X POST -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments -d {\"ref\":\"existingbranch\", \"environment\": \"PR\", \"auto_merge\": false, \"required_contexts\": []} # {\"id\": \"${app_id}\", \"othervar\": \"54321\"}"
    "Marked deployment as started."
    "Finished GitHub notification for pre_deployment event."
    "Finished dispatching notifications."
  )

  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="existingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="existingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, pre_deployment, PR" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="123456789"

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for pre_deployment event."
    "@curl -X POST -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments -d {\"ref\":\"feature/my-pr-branch\", \"environment\": \"PR\", \"auto_merge\": false, \"required_contexts\": []} # {\"id\": \"${app_id}\", \"othervar\": \"54321\"}"
    "Marked deployment as started."
    "Finished GitHub notification for pre_deployment event."
    "Finished dispatching notifications."
  )

  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="feature/my-pr-branch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_PR_NUMBER="123"
  export VORTEX_NOTIFY_LABEL="PR-123"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, pre_deployment, longer ID" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="12345678987"

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for pre_deployment event."
    "@curl -X POST -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments -d {\"ref\":\"existingbranch\", \"environment\": \"PR\", \"auto_merge\": false, \"required_contexts\": []} # {\"id\": \"${app_id}\", \"othervar\": \"54321\"}"
    "Marked deployment as started."
    "Finished GitHub notification for pre_deployment event."
    "Finished dispatching notifications."
  )

  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="existingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="existingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, pre_deployment, failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for pre_deployment event."
    '@curl -X POST -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments -d {"ref":"nonexistingbranch", "environment": "PR", "auto_merge": false, "required_contexts": []} # {"message": "No ref found for: nonexistingbranch","documentation_url": "https://docs.github.com/rest/deployments/deployments#create-a-deployment","status": "422"}'
    "Failed to get a deployment ID for a pre_deployment operation. Payload:"
    "Wait for GitHub checks to finish and try again."
    "-Marked deployment as finished."
  )

  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="pre_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="nonexistingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="nonexistingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_failure

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
    "@curl -X GET -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments?ref=existingbranch # [{\"id\": \"${app_id}\", \"othervar\": \"54321\"},{\"id\": \"98765432101\", \"othervar\": \"12345\"}]"
    "@curl -X POST -H Accept: application/vnd.github.v3+json -H Authorization: token token12345 https://api.github.com/repos/myorg/myrepo/deployments/${app_id}/statuses -s -d {\"state\":\"success\", \"environment_url\": \"https://develop.testproject.com\"} # {\"state\": \"success\", \"othervar\": \"54321\"}"
    "Marked deployment as finished."
    "Finished GitHub notification for post_deployment event."
    "Finished dispatching notifications."
  )
  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="existingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="existingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, post_deployment, longer ID" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="12345678987"
  mock_curl=$(mock_command "curl")

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for post_deployment event."
    "@curl -X GET -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments?ref=existingbranch # [{\"id\": \"${app_id}\", \"othervar\": \"54321\"},{\"id\": \"98765432101\", \"othervar\": \"12345\"}]"
    "@curl -X POST -H Accept: application/vnd.github.v3+json -H Authorization: token token12345 https://api.github.com/repos/myorg/myrepo/deployments/${app_id}/statuses -s -d {\"state\":\"success\", \"environment_url\": \"https://develop.testproject.com\"} # {\"state\": \"success\", \"othervar\": \"54321\"}"
    "Marked deployment as finished."
    "Finished GitHub notification for post_deployment event."
    "Finished dispatching notifications."
  )
  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="existingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="existingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, post_deployment, failure to get id" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_curl=$(mock_command "curl")

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for post_deployment event."
    "@curl -X GET -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments?ref=nonexistingbranch # []"
    "Failed to get a deployment ID for a post_deployment operation. Payload:"
    "Check that a pre_deployment notification was dispatched."
    "-Marked deployment as finished."
  )
  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="nonexistingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="nonexistingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_failure

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Notify: github, post_deployment, failure to set status" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  app_id="12345678987"
  mock_curl=$(mock_command "curl")

  declare -a STEPS=(
    "Started dispatching notifications."
    "Started GitHub notification for post_deployment event."
    "@curl -X GET -H Authorization: token token12345 -H Accept: application/vnd.github.v3+json -s https://api.github.com/repos/myorg/myrepo/deployments?ref=existingbranch # [{\"id\": \"${app_id}\", \"othervar\": \"54321\"},{\"id\": \"98765432101\", \"othervar\": \"12345\"}]"
    "@curl -X POST -H Accept: application/vnd.github.v3+json -H Authorization: token token12345 https://api.github.com/repos/myorg/myrepo/deployments/${app_id}/statuses -s -d {\"state\":\"success\", \"environment_url\": \"https://develop.testproject.com\"} # {\"state\": \"notsuccess\", \"othervar\": \"54321\"}"
    "Previous deployment was found, but was unable to update the deployment status. Payload:"
    "-Marked deployment as finished."
  )
  mocks="$(run_steps "setup")"

  export VORTEX_NOTIFY_CHANNELS="github"
  export VORTEX_NOTIFY_EVENT="post_deployment"
  export VORTEX_NOTIFY_GITHUB_TOKEN="token12345"
  export VORTEX_NOTIFY_GITHUB_REPOSITORY="myorg/myrepo"
  export VORTEX_NOTIFY_BRANCH="existingbranch"
  export VORTEX_NOTIFY_SHA="abc123def456"
  export VORTEX_NOTIFY_LABEL="existingbranch"
  export VORTEX_NOTIFY_ENVIRONMENT_URL="https://develop.testproject.com"
  run ./scripts/vortex/notify.sh
  assert_failure

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
