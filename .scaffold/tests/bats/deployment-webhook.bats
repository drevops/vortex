#!/usr/bin/env bats
#
# Test for webhook deployments.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash

@test "Missing variable checks" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  unset DREVOPS_DEPLOY_WEBHOOK_URL
  unset DREVOPS_DEPLOY_WEBHOOK_METHOD
  unset DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS
  run scripts/drevops/deploy-webhook.sh
  assert_failure
  assert_output_contains "Missing required value for DREVOPS_DEPLOY_WEBHOOK_URL."
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "200" 1
  export DREVOPS_DEPLOY_WEBHOOK_URL="https://example.com"
  unset DREVOPS_DEPLOY_WEBHOOK_METHOD
  unset DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS
  run scripts/drevops/deploy-webhook.sh
  assert_success
  assert_output_not_contains "Missing required value for DREVOPS_DEPLOY_WEBHOOK_METHOD."
  assert_output_not_contains "Missing required value for DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS."
  popd >/dev/null
}

@test "Successful webhook deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DREVOPS_DEPLOY_WEBHOOK_URL="https://example.com"
  export DREVOPS_DEPLOY_WEBHOOK_METHOD="GET"
  export DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS="200"
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "${DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS}" 1

  run scripts/drevops/deploy-webhook.sh
  assert_success
  assert_output_contains "Webhook call completed."
  assert_output_contains "Finished WEBHOOK deployment."
  popd >/dev/null
}

@test "Failed webhook deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  export DREVOPS_DEPLOY_WEBHOOK_URL="https://example.com"
  export DREVOPS_DEPLOY_WEBHOOK_METHOD="GET"
  export DREVOPS_DEPLOY_WEBHOOK_RESPONSE_STATUS="200"
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "400" 1

  run scripts/drevops/deploy-webhook.sh
  assert_failure
  assert_output_contains "Unable to complete webhook deployment."
  popd >/dev/null
}
