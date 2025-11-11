#!/usr/bin/env bats
#
# Test for main deployment router script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load ../_helper.bash

@test "Missing VORTEX_DEPLOY_TYPES variable" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES=""

  run scripts/vortex/deploy.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_TYPES"

  popd >/dev/null
}

@test "Skip all deployments with VORTEX_DEPLOY_SKIP" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_SKIP="1"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip all deployments."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "Deployment proceeds without skip flags" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Started deployment."
  assert_output_not_contains "Found flag to skip"

  popd >/dev/null
}

@test "Deployment proceeds with ALLOW_SKIP but no skip lists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}

@test "Skip deployment for single PR" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PRS="123"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found PR 123 in skip list."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "Skip deployment for PR in comma-separated list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR="456"
  export VORTEX_DEPLOY_SKIP_PRS="123,456,789"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found PR 456 in skip list."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "Allow deployment for PR not in skip list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR="999"
  export VORTEX_DEPLOY_SKIP_PRS="123,456,789"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_not_contains "Found PR 999 in skip list."
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}

@test "Skip deployment for single branch" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_BRANCH="feature/test"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found branch feature/test in skip list."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "Skip deployment for branch in comma-separated list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_BRANCH="hotfix/urgent"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test,hotfix/urgent,project/experimental"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_contains "Found branch hotfix/urgent in skip list."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "Allow deployment for branch not in skip list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_BRANCH="develop"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test,hotfix/urgent"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_not_contains "Found branch develop in skip list."
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}

@test "Deployment proceeds without ALLOW_SKIP despite skip lists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PRS="123"
  export VORTEX_DEPLOY_BRANCH="feature/test"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_not_contains "Found flag to skip a deployment."
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}

@test "Branch with forward slash is matched correctly" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_BRANCH="feature/my-feature"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/my-feature"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found branch feature/my-feature in skip list."
  assert_output_contains "Skipping deployment webhook."

  popd >/dev/null
}

@test "PR skip takes precedence when both PR and branch are set" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PRS="123"
  export VORTEX_DEPLOY_BRANCH="develop"
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found PR 123 in skip list."
  assert_output_contains "Skipping deployment webhook."
  assert_output_not_contains "Found branch"

  popd >/dev/null
}

@test "Artifact deployment type is routed when VORTEX_DEPLOY_TYPES contains artifact" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="artifact"
  export VORTEX_DEPLOY_ARTIFACT_SRC="${LOCAL_REPO_DIR}"
  export VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="git@example.com:repo.git"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME="Test User"
  export VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL="test@example.com"

  run scripts/vortex/deploy.sh
  assert_output_contains "Started deployment."
  assert_output_contains "Started ARTIFACT deployment."

  popd >/dev/null
}

@test "Skip applies to all deployment types" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook,artifact,container_registry"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_SKIP_PRS="123"

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found PR 123 in skip list."
  assert_output_contains "Skipping deployment webhook,artifact,container_registry."

  popd >/dev/null
}

@test "Empty PR variable does not cause skip check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_PR=""
  export VORTEX_DEPLOY_SKIP_PRS="123"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_not_contains "Found PR"
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}

@test "Empty branch variable does not cause skip check" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export VORTEX_DEPLOY_TYPES="webhook"
  export VORTEX_DEPLOY_WEBHOOK_URL="https://example.com"
  export VORTEX_DEPLOY_ALLOW_SKIP="1"
  export VORTEX_DEPLOY_BRANCH=""
  export VORTEX_DEPLOY_SKIP_BRANCHES="feature/test"

  mock_deploy_webhook=$(mock_command "scripts/vortex/deploy-webhook.sh")
  mock_set_output "${mock_deploy_webhook}" "Webhook deployment completed" 0

  run scripts/vortex/deploy.sh
  assert_success
  assert_output_contains "Found flag to skip a deployment."
  assert_output_not_contains "Found branch"
  assert_output_not_contains "Skipping deployment"

  popd >/dev/null
}
