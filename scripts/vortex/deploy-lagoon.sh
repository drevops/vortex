#!/usr/bin/env bash
##
# Deploy via Lagoon CLI.
#
# @see https://github.com/amazeeio/lagoon-cli
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Deployment action.
#
# Values can be one of: deploy, deploy_override_db, destroy.
# - deploy: Deploy code and preserve database in the environment.
# - deploy_override_db: Deploy code and override database in the environment.
# - destroy: Destroy the environment (if the provider supports it).
VORTEX_DEPLOY_ACTION="${VORTEX_DEPLOY_ACTION:-create}"

# The Lagoon project to perform deployment for.
LAGOON_PROJECT="${LAGOON_PROJECT:-}"

# The Lagoon branch to deploy.
VORTEX_DEPLOY_BRANCH="${VORTEX_DEPLOY_BRANCH:-}"

# The PR number to deploy.
VORTEX_DEPLOY_PR="${VORTEX_DEPLOY_PR:-}"

# The PR head branch to deploy.
VORTEX_DEPLOY_PR_HEAD="${VORTEX_DEPLOY_PR_HEAD:-}"

# The PR base branch (the branch the PR is raised against). Defaults to 'develop'.
VORTEX_DEPLOY_PR_BASE_BRANCH="${VORTEX_DEPLOY_PR_BASE_BRANCH:-develop}"

# The Lagoon instance name to interact with.
VORTEX_DEPLOY_LAGOON_INSTANCE="${VORTEX_DEPLOY_LAGOON_INSTANCE:-amazeeio}"

# The Lagoon instance GraphQL endpoint to interact with.
VORTEX_DEPLOY_LAGOON_INSTANCE_GRAPHQL="${VORTEX_DEPLOY_LAGOON_INSTANCE_GRAPHQL:-https://api.lagoon.amazeeio.cloud/graphql}"

# The Lagoon instance hostname to interact with.
VORTEX_DEPLOY_LAGOON_INSTANCE_HOSTNAME="${VORTEX_DEPLOY_LAGOON_INSTANCE_HOSTNAME:-ssh.lagoon.amazeeio.cloud}"

# The Lagoon instance port to interact with.
VORTEX_DEPLOY_LAGOON_INSTANCE_PORT="${VORTEX_DEPLOY_LAGOON_INSTANCE_PORT:-32222}"

# SSH key fingerprint used to connect to remote. If not used, the currently
# loaded default SSH key (the key used for code checkout) will be used or
# deployment will fail with an error if the default SSH key is not loaded.
# In most cases, the default SSH key does not work (because it is a read-only
# key used by CircleCI to checkout code from git), so you should add another
# deployment key.
VORTEX_DEPLOY_SSH_FINGERPRINT="${VORTEX_DEPLOY_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_DEPLOY_SSH_FILE="${VORTEX_DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
VORTEX_LAGOONCLI_PATH="${VORTEX_LAGOONCLI_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
VORTEX_LAGOONCLI_FORCE_INSTALL="${VORTEX_LAGOONCLI_FORCE_INSTALL:-}"

# Lagoon CLI version to use.
VORTEX_LAGOONCLI_VERSION="${VORTEX_LAGOONCLI_VERSION:-v0.32.0}"

# Flag to control failure behavior when Lagoon environment limits are exceeded.
# When set to 0, the deployment will exit with success instead of failure.
# When set to 1, the deployment will fail.
VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED="${VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED:-0}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }

# Check if error output contains Lagoon environment limit exceeded message.
# Returns 0 if limit exceeded error found, 1 otherwise.
is_lagoon_env_limit_exceeded() {
  local error_output="${1:-}"
  echo "${error_output}" | grep -q "exceed"
}
# @formatter:on

# Track exit status to return at the end.
exit_code=0

info "Started LAGOON deployment."

## Check all required values.
[ -z "${LAGOON_PROJECT}" ] && fail "Missing required value for LAGOON_PROJECT." && exit 1
{ [ -z "${VORTEX_DEPLOY_BRANCH}" ] && [ -z "${VORTEX_DEPLOY_PR}" ]; } && fail "Missing required value for VORTEX_DEPLOY_BRANCH or VORTEX_DEPLOY_PR." && exit 1

export VORTEX_SSH_PREFIX="DEPLOY" && . ./scripts/vortex/setup-ssh.sh

if ! command -v lagoon >/dev/null || [ -n "${VORTEX_LAGOONCLI_FORCE_INSTALL}" ]; then
  task "Installing Lagoon CLI."

  platform=$(uname -s | tr '[:upper:]' '[:lower:]')
  arch_suffix=$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/')
  download_url="https://github.com/uselagoon/lagoon-cli/releases/download/${VORTEX_LAGOONCLI_VERSION}/lagoon-cli-${VORTEX_LAGOONCLI_VERSION}-${platform}-${arch_suffix}"

  note "Downloading Lagoon CLI from ${download_url}."
  curl -fSLs -o "${VORTEX_LAGOONCLI_PATH}/lagoon" "${download_url}"

  note "Installing Lagoon CLI to ${VORTEX_LAGOONCLI_PATH}/lagoon."
  chmod +x "${VORTEX_LAGOONCLI_PATH}/lagoon"
  export PATH="${PATH}:${VORTEX_LAGOONCLI_PATH}"
fi

for cmd in lagoon curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

task "Configuring Lagoon instance."
#shellcheck disable=SC2218
lagoon config add --force --lagoon "${VORTEX_DEPLOY_LAGOON_INSTANCE}" --graphql "${VORTEX_DEPLOY_LAGOON_INSTANCE_GRAPHQL}" --hostname "${VORTEX_DEPLOY_LAGOON_INSTANCE_HOSTNAME}" --port "${VORTEX_DEPLOY_LAGOON_INSTANCE_PORT}"

lagoon() { command lagoon --force --skip-update-check --ssh-key "${VORTEX_DEPLOY_SSH_FILE}" --lagoon "${VORTEX_DEPLOY_LAGOON_INSTANCE}" --project "${LAGOON_PROJECT}" "$@"; }

# ACTION: 'destroy'
# Explicitly specifying "destroy" action as a failsafe.
if [ "${VORTEX_DEPLOY_ACTION}" = "destroy" ]; then
  task "Destroying environment: project ${LAGOON_PROJECT}, branch: ${VORTEX_DEPLOY_BRANCH}."
  lagoon delete environment --environment "${VORTEX_DEPLOY_BRANCH}" || true

# ACTION: 'deploy' OR 'deploy_override_db'
else
  # Deploy PR.
  if [ -n "${VORTEX_DEPLOY_PR:-}" ]; then
    deploy_pr_full="pr-${VORTEX_DEPLOY_PR}"

    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    task "Discovering existing environments for PR deployments."
    lagoon list environments --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("pullrequest")) | .name' /tmp/lagoon-envs.json /dev/null 2>&1 || echo '')"

    is_redeploy=0
    for name in ${names}; do
      if [ "${deploy_pr_full}" = "${name}" ]; then
        note "Found already deployed environment for PR \"${VORTEX_DEPLOY_PR}\"."
        is_redeploy=1
        break
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy:-}" = "1" ]; then
      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      task "Setting a DB overwrite flag to 0."
      lagoon update variable --environment "${deploy_pr_full}" --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global || true

      # Override DB during re-deployment.
      if [ "${VORTEX_DEPLOY_ACTION}" = "deploy_override_db" ]; then
        task "Adding a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon update variable --environment "${deploy_pr_full}" --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global || true
      fi

      task "Redeploying environment: project ${LAGOON_PROJECT}, PR: ${VORTEX_DEPLOY_PR}."
      deploy_output=$(lagoon deploy pullrequest --number "${VORTEX_DEPLOY_PR}" --base-branch-name "${VORTEX_DEPLOY_PR_BASE_BRANCH}" --base-branch-ref "origin/${VORTEX_DEPLOY_PR_BASE_BRANCH}" --head-branch-name "${VORTEX_DEPLOY_BRANCH}" --head-branch-ref "${VORTEX_DEPLOY_PR_HEAD}" --title "${deploy_pr_full}" 2>&1) || exit_code=$?
      exit_code=${exit_code:-0}
      if is_lagoon_env_limit_exceeded "${deploy_output}"; then
        note "Lagoon environment limit exceeded."
        [ "${VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED}" = "0" ] && exit_code=0
      fi

      if [ "${VORTEX_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        task "Waiting for deployment to be queued."
        sleep 10

        task "Removing a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon update variable --environment "${deploy_pr_full}" --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global || true
      fi

    # Deployment of the fresh environment.
    else
      # If PR deployments are not configured in Lagoon - it will filter it out and will not deploy.
      task "Deploying environment: project ${LAGOON_PROJECT}, PR: ${VORTEX_DEPLOY_PR}."
      deploy_output=$(lagoon deploy pullrequest --number "${VORTEX_DEPLOY_PR}" --base-branch-name "${VORTEX_DEPLOY_PR_BASE_BRANCH}" --base-branch-ref "origin/${VORTEX_DEPLOY_PR_BASE_BRANCH}" --head-branch-name "${VORTEX_DEPLOY_BRANCH}" --head-branch-ref "${VORTEX_DEPLOY_PR_HEAD}" --title "${deploy_pr_full}" 2>&1) || exit_code=$?
      exit_code=${exit_code:-0}
      if is_lagoon_env_limit_exceeded "${deploy_output}"; then
        note "Lagoon environment limit exceeded."
        [ "${VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED}" = "0" ] && exit_code=0
      fi
    fi

  # Deploy branch.
  else
    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    task "Discovering existing environments for branch deployments."
    lagoon list environments --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("branch")) | .name' /tmp/lagoon-envs.json /dev/null 2>&1 || echo '')"

    is_redeploy=0
    for name in ${names}; do
      if [ "${VORTEX_DEPLOY_BRANCH:-}" = "${name}" ]; then
        note "Found already deployed environment for branch \"${VORTEX_DEPLOY_BRANCH}\"."
        is_redeploy=1
        break
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy:-}" = "1" ]; then
      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      task "Setting a DB overwrite flag to 0."
      lagoon update variable --environment "${VORTEX_DEPLOY_BRANCH}" --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global || true

      # Override DB during re-deployment.
      if [ "${VORTEX_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        task "Adding a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon update variable --environment "${VORTEX_DEPLOY_BRANCH}" --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global || true
      fi

      task "Redeploying environment: project ${LAGOON_PROJECT}, branch: ${VORTEX_DEPLOY_BRANCH}."
      deploy_output=$(lagoon deploy latest --environment "${VORTEX_DEPLOY_BRANCH}" 2>&1) || exit_code=$?
      exit_code=${exit_code:-0}
      if is_lagoon_env_limit_exceeded "${deploy_output}"; then
        note "Lagoon environment limit exceeded."
        [ "${VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED}" = "0" ] && exit_code=0
      fi

      if [ "${VORTEX_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        task "Waiting for deployment to be queued."
        sleep 10

        task "Removing a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon update variable --environment "${VORTEX_DEPLOY_BRANCH}" --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global || true
      fi

    # Deployment of the fresh environment.
    else
      # If current branch deployments does not match a regex in Lagoon - it will filter it out and will not deploy.
      task "Deploying environment: project ${LAGOON_PROJECT}, branch: ${VORTEX_DEPLOY_BRANCH}."
      deploy_output=$(lagoon deploy branch --branch "${VORTEX_DEPLOY_BRANCH}" 2>&1) || exit_code=$?
      exit_code=${exit_code:-0}
      if is_lagoon_env_limit_exceeded "${deploy_output}"; then
        note "Lagoon environment limit exceeded."
        [ "${VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED}" = "0" ] && exit_code=0
      fi
    fi
  fi
fi

if [ "${exit_code}" = "0" ]; then
  pass "Finished LAGOON deployment."
else
  fail "LAGOON deployment completed with errors."
fi

exit "${exit_code}"
