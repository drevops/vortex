#!/usr/bin/env bats
#
# Tests for scripts/vortex/deploy-lagoon.sh script.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load ../_helper.bash

@test "Tag deployment mode skips Lagoon deployment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_MODE="tag"

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  assert_output_contains "Started LAGOON deployment."
  assert_output_contains "Lagoon does not support tag deployments. Skipping."
  assert_output_contains "Finished LAGOON deployment."

  popd >/dev/null
}

@test "Deploy fails when LAGOON_PROJECT variable is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove any .env file to ensure clean state
  [ -f .env ] && rm .env
  [ -f .env.local ] && rm .env.local

  # Create minimal .env without LAGOON_PROJECT
  echo "# Test env" >.env

  unset LAGOON_PROJECT
  export VORTEX_DEPLOY_BRANCH="test-branch"

  run scripts/vortex/deploy-lagoon.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_LAGOON_PROJECT or LAGOON_PROJECT."

  popd >/dev/null
}

@test "Deploy fails when both VORTEX_DEPLOY_BRANCH and VORTEX_DEPLOY_PR are missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export LAGOON_PROJECT="test_project"
  unset VORTEX_DEPLOY_BRANCH
  unset VORTEX_DEPLOY_PR

  run scripts/vortex/deploy-lagoon.sh
  assert_failure
  assert_output_contains "Missing required value for VORTEX_DEPLOY_LAGOON_BRANCH or VORTEX_DEPLOY_BRANCH or VORTEX_DEPLOY_LAGOON_PR or VORTEX_DEPLOY_PR."

  popd >/dev/null
}

@test "Branch: Deploy fresh environment no existing environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"
  export VORTEX_DEPLOY_LAGOON_INSTANCE_GRAPHQL="https://api.lagoon.amazeeio.cloud/graphql"
  export VORTEX_DEPLOY_LAGOON_INSTANCE_HOSTNAME="ssh.lagoon.amazeeio.cloud"
  export VORTEX_DEPLOY_LAGOON_INSTANCE_PORT="32222"

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for branch deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # {\"data\":[]}"
    "Deploying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy branch --branch test-branch"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Branch: Redeploy existing environment preserve database" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  # Mock existing environment
  local existing_env_json='{"data":[{"name":"test-branch","deploytype":"branch"}]}'

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for branch deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # ${existing_env_json}"
    'Found already deployed environment for branch "test-branch".'
    "Setting a DB overwrite flag to 0."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment test-branch --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Redeploying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy latest --environment test-branch"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Branch: Redeploy existing environment with database override" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_ACTION="deploy_override_db"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  # Mock existing environment
  local existing_env_json='{"data":[{"name":"test-branch","deploytype":"branch"}]}'

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for branch deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # ${existing_env_json}"
    'Found already deployed environment for branch "test-branch".'
    "Setting a DB overwrite flag to 0."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment test-branch --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Adding a DB import override flag for the current deployment."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment test-branch --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global"
    "Redeploying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy latest --environment test-branch"
    "Waiting for deployment to be queued."
    "@sleep 10"
    "Removing a DB import override flag for the current deployment."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment test-branch --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  # Mock commands are handled by the steps

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "PR: Deploy fresh environment no existing environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_BRANCH="feature-branch"
  export VORTEX_DEPLOY_PR_HEAD="origin/feature-branch"
  export VORTEX_DEPLOY_PR_BASE_BRANCH="develop"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for PR deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # {\"data\":[]}"
    "Deploying environment: project test_project, PR: 123."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy pullrequest --number 123 --base-branch-name develop --base-branch-ref origin/develop --head-branch-name feature-branch --head-branch-ref origin/feature-branch --title pr-123"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  # Mock commands are handled by the steps

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "PR: Redeploy existing environment preserve database" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_PR="123"
  export VORTEX_DEPLOY_BRANCH="feature-branch"
  export VORTEX_DEPLOY_PR_HEAD="origin/feature-branch"
  export VORTEX_DEPLOY_PR_BASE_BRANCH="develop"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  # Mock existing PR environment
  local existing_pr_env_json='{"data":[{"name":"pr-123","deploytype":"pullrequest"}]}'

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for PR deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # ${existing_pr_env_json}"
    'Found already deployed environment for PR "123".'
    "Setting a DB overwrite flag to 0."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment pr-123 --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Redeploying environment: project test_project, PR: 123."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy pullrequest --number 123 --base-branch-name develop --base-branch-ref origin/develop --head-branch-name feature-branch --head-branch-ref origin/feature-branch --title pr-123"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  # Mock commands are handled by the steps

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "PR: Redeploy existing environment with database override" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_PR="456"
  export VORTEX_DEPLOY_BRANCH="feature-branch"
  export VORTEX_DEPLOY_PR_HEAD="origin/feature-branch"
  export VORTEX_DEPLOY_PR_BASE_BRANCH="develop"
  export VORTEX_DEPLOY_ACTION="deploy_override_db"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  # Mock existing PR environment
  local existing_pr_env_json='{"data":[{"name":"pr-456","deploytype":"pullrequest"}]}'

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for PR deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # ${existing_pr_env_json}"
    'Found already deployed environment for PR "456".'
    "Setting a DB overwrite flag to 0."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment pr-456 --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Adding a DB import override flag for the current deployment."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment pr-456 --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global"
    "Redeploying environment: project test_project, PR: 456."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy pullrequest --number 456 --base-branch-name develop --base-branch-ref origin/develop --head-branch-name feature-branch --head-branch-ref origin/feature-branch --title pr-456"
    "Waiting for deployment to be queued."
    "@sleep 10"
    "Removing a DB import override flag for the current deployment."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project update variable --environment pr-456 --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  # Mock commands are handled by the steps
  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Environment: Destroy existing environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_ACTION="destroy"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Destroying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project delete environment --environment test-branch"
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  # Mock commands are handled by the steps

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Branch: Environment limit exceeded with FAIL flag set to 0 (continue successfully)" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"
  export VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED="0"

  # Mock lagoon command to return environment limit exceeded error
  local limit_error="Error: graphql: 'test-branch' would exceed the configured limit of 5 development environments for project test_project"

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for branch deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # {\"data\":[]}"
    "Deploying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy branch --branch test-branch # 1 # ${limit_error}"
    "Lagoon environment limit exceeded."
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Branch: Environment limit exceeded with FAIL flag set to 1 (fail deployment)" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_BRANCH="test-branch"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"
  export VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED="1"

  # Mock lagoon command to return environment limit exceeded error
  local limit_error="Error: graphql: 'test-branch' would exceed the configured limit of 5 development environments for project test_project"

  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for branch deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # {\"data\":[]}"
    "Deploying environment: project test_project, branch: test-branch."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy branch --branch test-branch # 1 # ${limit_error}"
    "Lagoon environment limit exceeded."
    "[FAIL] LAGOON deployment completed with errors."
  )

  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-lagoon.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "PR: Environment limit exceeded with FAIL flag set to 0 (continue successfully)" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_ssh_key_prepare
  fixture_ssh_key

  export LAGOON_PROJECT="test_project"
  export VORTEX_DEPLOY_PR="133"
  export VORTEX_DEPLOY_BRANCH="feature-branch"
  export VORTEX_DEPLOY_PR_HEAD="origin/feature-branch"
  export VORTEX_DEPLOY_PR_BASE_BRANCH="develop"
  export VORTEX_DEPLOY_LAGOON_INSTANCE="amazeeio"
  export VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED="0"

  # Mock lagoon command to return environment limit exceeded error
  local limit_error="Error: graphql: 'pr-133' would exceed the configured limit of 5 development environments for project website"

  # shellcheck disable=SC2034
  declare -a STEPS=(
    "Started LAGOON deployment."
    "Configuring Lagoon instance."
    "@lagoon config add --force --lagoon amazeeio --graphql https://api.lagoon.amazeeio.cloud/graphql --hostname ssh.lagoon.amazeeio.cloud --port 32222"
    "Discovering existing environments for PR deployments."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project list environments --output-json --pretty # {\"data\":[]}"
    "Deploying environment: project test_project, PR: 133."
    "@lagoon --force --skip-update-check --ssh-key ${HOME}/.ssh/id_rsa --lagoon amazeeio --project test_project deploy pullrequest --number 133 --base-branch-name develop --base-branch-ref origin/develop --head-branch-name feature-branch --head-branch-ref origin/feature-branch --title pr-133 # 1 # ${limit_error}"
    "Lagoon environment limit exceeded."
    "Finished LAGOON deployment."
  )

  mocks="$(run_steps "setup")"

  run scripts/vortex/deploy-lagoon.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}
