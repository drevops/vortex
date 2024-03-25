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
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Deployment action.
#
# Values can be one of: deploy, deploy_override_db, destroy.
# - deploy: Deploy code and preserve database in the environment.
# - deploy_override_db: Deploy code and override database in the environment.
# - destroy: Destroy the environment (if the provider supports it).
DREVOPS_DEPLOY_ACTION="${DREVOPS_DEPLOY_ACTION:-create}"

# The Lagoon project to perform deployment for.
LAGOON_PROJECT="${LAGOON_PROJECT:-}"

# The Lagoon branch to deploy.
DREVOPS_DEPLOY_BRANCH="${DREVOPS_DEPLOY_BRANCH:-}"

# The PR number to deploy.
DREVOPS_DEPLOY_PR="${DREVOPS_DEPLOY_PR:-}"

# The PR head branch to deploy.
DREVOPS_DEPLOY_PR_HEAD="${DREVOPS_DEPLOY_PR_HEAD:-}"

# The PR base branch (the branch the PR is raised against). Defaults to 'develop'.
DREVOPS_DEPLOY_PR_BASE_BRANCH="${DREVOPS_DEPLOY_PR_BASE_BRANCH:-develop}"

# The Lagoon instance name to interact with.
DREVOPS_DEPLOY_LAGOON_INSTANCE="${DREVOPS_DEPLOY_LAGOON_INSTANCE:-amazeeio}"

# The Lagoon instance GraphQL endpoint to interact with.
DREVOPS_DEPLOY_LAGOON_INSTANCE_GRAPHQL="${DREVOPS_DEPLOY_LAGOON_INSTANCE_GRAPHQL:-https://api.lagoon.amazeeio.cloud/graphql}"

# The Lagoon instance hostname to interact with.
DREVOPS_DEPLOY_LAGOON_INSTANCE_HOSTNAME="${DREVOPS_DEPLOY_LAGOON_INSTANCE_HOSTNAME:-ssh.lagoon.amazeeio.cloud}"

# The Lagoon instance port to interact with.
DREVOPS_DEPLOY_LAGOON_INSTANCE_PORT="${DREVOPS_DEPLOY_LAGOON_INSTANCE_PORT:-32222}"

# SSH key fingerprint used to connect to remote. If not used, the currently
# loaded default SSH key (the key used for code checkout) will be used or
# deployment will fail with an error if the default SSH key is not loaded.
# In most cases, the default SSH key does not work (because it is a read-only
# key used by CircleCI to checkout code from git), so you should add another
# deployment key.
DREVOPS_DEPLOY_SSH_FINGERPRINT="${DREVOPS_DEPLOY_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DREVOPS_DEPLOY_SSH_FILE="${DREVOPS_DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH="${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL="${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL:-}"

# Lagoon CLI version to use.
DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION="${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION:-latest}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started LAGOON deployment."

## Check all required values.
[ -z "${LAGOON_PROJECT}" ] && fail "Missing required value for LAGOON_PROJECT." && exit 1
{ [ -z "${DREVOPS_DEPLOY_BRANCH}" ] && [ -z "${DREVOPS_DEPLOY_PR}" ]; } && fail "Missing required value for DREVOPS_DEPLOY_BRANCH or DREVOPS_DEPLOY_PR." && exit 1

DREVOPS_SSH_PREFIX="DEPLOY" ./scripts/drevops/setup-ssh.sh

if ! command -v lagoon >/dev/null || [ -n "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL}" ]; then
  note "Installing Lagoon CLI."

  lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/latest"
  if [ "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION}" != "latest" ]; then
    lagooncli_download_url="https://api.github.com/repos/uselagoon/lagoon-cli/releases/tags/${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_VERSION}"
  fi

  curl -sL "${lagooncli_download_url}" |
    grep "browser_download_url" |
    grep -i "$(uname -s)-amd64\"$" |
    cut -d '"' -f 4 |
    xargs curl -L -o "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"
  chmod +x "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"
  export PATH="${PATH}:${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}"
fi

note "Configuring Lagoon instance."
#shellcheck disable=SC2218
lagoon config add --force -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" -g "${DREVOPS_DEPLOY_LAGOON_INSTANCE_GRAPHQL}" -H "${DREVOPS_DEPLOY_LAGOON_INSTANCE_HOSTNAME}" -P "${DREVOPS_DEPLOY_LAGOON_INSTANCE_PORT}"
lagoon() { command lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" -p "${LAGOON_PROJECT}" "$@"; }

# ACTION: 'destroy'
# Explicitly specifying "destroy" action as a failsafe.
if [ "${DREVOPS_DEPLOY_ACTION}" = "destroy" ]; then
  note "Destroying environment: project ${LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_BRANCH}."
  lagoon delete environment -e "${DREVOPS_DEPLOY_BRANCH}" || true

# ACTION: 'deploy' OR 'deploy_override_db'
else
  # Deploy PR.
  if [ -n "${DREVOPS_DEPLOY_PR:-}" ]; then
    deploy_pr_full="pr-${DREVOPS_DEPLOY_PR}"

    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    lagoon list environments --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("pullrequest")) | .name' /tmp/lagoon-envs.json /dev/null 2>&1 || echo '')"

    is_redeploy=0
    for name in ${names}; do
      if [ "${deploy_pr_full}" = "${name}" ]; then
        note "Found already deployed environment for PR \"${DREVOPS_DEPLOY_PR}\"."
        is_redeploy=1
        break
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy:-}" = "1" ]; then

      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      lagoon update variable -e "${deploy_pr_full}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global || true

      # Override DB during re-deployment.
      if [ "${DREVOPS_DEPLOY_ACTION}" = "deploy_override_db" ]; then
        note "Add a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon update variable -e "${deploy_pr_full}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 1 -S global || true
      fi

      note "Redeploying environment: project ${LAGOON_PROJECT}, PR: ${DREVOPS_DEPLOY_PR}."
      lagoon deploy pullrequest -n "${DREVOPS_DEPLOY_PR}" --baseBranchName "${DREVOPS_DEPLOY_PR_BASE_BRANCH}" -R "origin/${DREVOPS_DEPLOY_PR_BASE_BRANCH}" -H "${DREVOPS_DEPLOY_BRANCH}" -M "${DREVOPS_DEPLOY_PR_HEAD}" -t "${deploy_pr_full}"

      if [ "${DREVOPS_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        note "Waiting for deployment to be queued."
        sleep 10

        note "Remove a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon update variable -e "${deploy_pr_full}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global || true
      fi

    # Deployment of the fresh environment.
    else
      # If PR deployments are not configured in Lagoon - it will filter it out and will not deploy.
      note "Deploying environment: project ${LAGOON_PROJECT}, PR: ${DREVOPS_DEPLOY_PR}."
      lagoon deploy pullrequest -n "${DREVOPS_DEPLOY_PR}" --baseBranchName "${DREVOPS_DEPLOY_PR_BASE_BRANCH}" -R "origin/${DREVOPS_DEPLOY_PR_BASE_BRANCH}" -H "${DREVOPS_DEPLOY_BRANCH}" -M "${DREVOPS_DEPLOY_PR_HEAD}" -t "${deploy_pr_full}"
    fi

  # Deploy branch.
  else
    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    lagoon list environments --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("branch")) | .name' /tmp/lagoon-envs.json /dev/null 2>&1 || echo '')"

    is_redeploy=0
    for name in ${names}; do
      if [ "${DREVOPS_DEPLOY_BRANCH:-}" = "${name}" ]; then
        note "Found already deployed environment for branch \"${DREVOPS_DEPLOY_BRANCH}\"."
        is_redeploy=1
        break
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy:-}" = "1" ]; then

      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      lagoon update variable -e "${DREVOPS_DEPLOY_BRANCH}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global || true

      # Override DB during re-deployment.
      if [ "${DREVOPS_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        note "Add a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon update variable -e "${DREVOPS_DEPLOY_BRANCH}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 1 -S global || true
      fi

      note "Redeploying environment: project ${LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_BRANCH}."
      lagoon deploy latest -e "${DREVOPS_DEPLOY_BRANCH}" || true

      if [ "${DREVOPS_DEPLOY_ACTION:-}" = "deploy_override_db" ]; then
        note "Waiting for deployment to be queued."
        sleep 10

        note "Remove a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon update variable -e "${DREVOPS_DEPLOY_BRANCH}" -N DREVOPS_PROVISION_OVERRIDE_DB -V 0 -S global || true
      fi

    # Deployment of the fresh environment.
    else
      # If current branch deployments does not match a regex in Lagoon - it will filter it out and will not deploy.
      note "Deploying environment: project ${LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_BRANCH}."
      lagoon deploy branch -b "${DREVOPS_DEPLOY_BRANCH}"
    fi
  fi
fi

pass "Finished LAGOON deployment."
