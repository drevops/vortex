#!/usr/bin/env bash
##
# Deploy via Lagoon CLI.
#
# @see https://github.com/amazeeio/lagoon-cli

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Deploy action.
# Values can be one of: deploy, deploy_override_db, destroy.
DREVOPS_DEPLOY_LAGOON_ACTION="${DREVOPS_DEPLOY_LAGOON_ACTION:-create}"

# The Lagoon project to perform deployment for.
DREVOPS_DEPLOY_LAGOON_PROJECT="${DREVOPS_DEPLOY_LAGOON_PROJECT:-}"

# The Lagoon branch to deploy.
DREVOPS_DEPLOY_LAGOON_BRANCH="${DREVOPS_DEPLOY_LAGOON_BRANCH:-}"

# The PR number to deploy.
DREVOPS_DEPLOY_LAGOON_PR="${DREVOPS_DEPLOY_LAGOON_PR:-}"

# The PR head branch to deploy.
DREVOPS_DEPLOY_LAGOON_PR_HEAD="${DREVOPS_DEPLOY_LAGOON_PR_HEAD:-}"

# The PR base branch (the branch the PR is raised against). Defaults to 'develop'.
DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH="${DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH:-develop}"

# The Lagoon instance to interact with.
DREVOPS_DEPLOY_LAGOON_INSTANCE="${DREVOPS_DEPLOY_LAGOON_INSTANCE:-amazeeio}"

# SSH key fingerprint used to connect to remote. If not used, the currently
# loaded default SSH key (the key used for code checkout) will be used or
# deployment will fail with an error if the default SSH key is not loaded.
# In most cases, the default SSH key does not work (because it is a read-only
# key used by CircleCI to checkout code from git), so you should add another
# deployment key.
DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT="${DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DREVOPS_DEPLOY_LAGOON_SSH_FILE="${DREVOPS_DEPLOY_LAGOON_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH="${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL="${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL:-}"

# ------------------------------------------------------------------------------

echo "==> Started LAGOON deployment."

## Check all required values.
[ -z "${DREVOPS_DEPLOY_LAGOON_PROJECT}" ] && echo "Missing required value for DREVOPS_DEPLOY_LAGOON_PROJECT." && exit 1
{ [ -z "${DREVOPS_DEPLOY_LAGOON_BRANCH}" ] && [ -z "${DREVOPS_DEPLOY_LAGOON_PR}" ]; } && echo "Missing required value for DREVOPS_DEPLOY_LAGOON_BRANCH or DREVOPS_DEPLOY_LAGOON_PR." && exit 1

# Use custom deploy key if fingerprint is provided.
if [ -n "${DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT}" ]; then
  echo "==> Custom deployment key is provided."
  DREVOPS_DEPLOY_LAGOON_SSH_FILE="${DREVOPS_DEPLOY_LAGOON_SSH_FINGERPRINT//:}"
  DREVOPS_DEPLOY_LAGOON_SSH_FILE="${HOME}/.ssh/id_rsa_${DREVOPS_DEPLOY_LAGOON_SSH_FILE//\"}"
fi

[ ! -f "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" ] && echo "ERROR: SSH key file ${DREVOPS_DEPLOY_LAGOON_SSH_FILE} does not exist." && exit 1

if ssh-add -l | grep -q "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}"; then
  echo "==> SSH agent has ${DREVOPS_DEPLOY_LAGOON_SSH_FILE} key loaded."
else
  echo "==> SSH agent does not have default key loaded. Trying to load."
  # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
  ssh-add -D > /dev/null
  ssh-add "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}"
fi

# Disable strict host key checking in CI.
[ -n "${CI}" ] && mkdir -p "${HOME}/.ssh/" && echo -e "\nHost *\n\tStrictHostKeyChecking no\n\tUserKnownHostsFile /dev/null\n" >> "${HOME}/.ssh/config"

if ! command -v lagoon >/dev/null || [ -n "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL}" ]; then
  echo "==> Installing Lagoon CLI."
  curl -sL https://api.github.com/repos/amazeeio/lagoon-cli/releases/latest \
    | grep "browser_download_url" \
    | grep -i "$(uname -s)-amd64\"$" \
    | cut -d '"' -f 4 \
    | xargs curl -L -o "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"
  chmod +x "${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}/lagoon"
  export PATH="${PATH}:${DREVOPS_DEPLOY_LAGOON_LAGOONCLI_BIN_PATH}"
fi

# ACTION: 'destroy'
# Explicitly specifying "destroy" action as a failsafe.
if [ "${DREVOPS_DEPLOY_LAGOON_ACTION}" = "destroy" ]; then
  echo "  > Destroying environment: project ${DREVOPS_DEPLOY_LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_LAGOON_BRANCH}."
  lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete environment -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" || true

# ACTION: 'deploy' OR 'deploy_override_db'
else
  # Deploy PR.
  if [ -n "${DREVOPS_DEPLOY_LAGOON_PR}" ]; then
    deploy_pr_full="pr-${DREVOPS_DEPLOY_LAGOON_PR}"

    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" list environments -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("pullrequest")) | .name' /tmp/lagoon-envs.json 2>&1 /dev/null || echo '')"

    is_redeploy=0
    for name in $names; do
      if [ "${deploy_pr_full}" = "${name}" ]; then
        echo "  > Found already deployed environment for PR \"${DREVOPS_DEPLOY_LAGOON_PR}\"."
        is_redeploy=1;
        break;
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy}" = "1" ]; then

      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 0 -S global || true

      # Override DB during re-deployment.
      if [ "${DREVOPS_DEPLOY_LAGOON_ACTION}" = "deploy_override_db" ]; then
        echo "  > Add a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 1 -S global || true
      fi

      echo "  > Redeploying environment: project ${DREVOPS_DEPLOY_LAGOON_PROJECT}, PR: ${DREVOPS_DEPLOY_LAGOON_PR}."
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" deploy pullrequest -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -n "${DREVOPS_DEPLOY_LAGOON_PR}" --baseBranchName "${DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH}" -R "origin/${DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH}" -H "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -M "${DREVOPS_DEPLOY_LAGOON_PR_HEAD}" -t "${deploy_pr_full}"

      if [ "${DREVOPS_DEPLOY_LAGOON_ACTION}" = "deploy_override_db" ]; then
        echo "  > Waiting for deployment to be queued."
        sleep 10

        echo "  > Remove a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${deploy_pr_full}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 0 -S global || true
      fi

    # Deployment of the fresh environment.
    else
      # If PR deployments are not configured in Lagoon - it will filter it out and will not deploy.
      echo "  > Deploying environment: project ${DREVOPS_DEPLOY_LAGOON_PROJECT}, PR: ${DREVOPS_DEPLOY_LAGOON_PR}."
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" deploy pullrequest -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -n "${DREVOPS_DEPLOY_LAGOON_PR}" --baseBranchName "${DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH}" -R "origin/${DREVOPS_DEPLOY_LAGOON_PR_BASE_BRANCH}" -H "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -M "${DREVOPS_DEPLOY_LAGOON_PR_HEAD}" -t "${deploy_pr_full}"
    fi

  # Deploy branch.
  else
    # Discover all available environments to check if this is a fresh deployment
    # or a re-deployment of the existing environment.
    lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" list environments -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" --output-json --pretty >/tmp/lagoon-envs.json
    names="$(jq -r '.data[] | select(.deploytype | contains("branch")) | .name' /tmp/lagoon-envs.json 2>&1 /dev/null || echo '')"

    is_redeploy=0
    for name in $names; do
      if [ "${DREVOPS_DEPLOY_LAGOON_BRANCH}" = "${name}" ]; then
        echo "  > Found already deployed environment for branch \"${DREVOPS_DEPLOY_LAGOON_BRANCH}\"."
        is_redeploy=1;
        break;
      fi
    done

    # Re-deployment of the existing environment.
    if [ "${is_redeploy}" = "1" ]; then

      # Explicitly set DB overwrite flag to 0 due to a bug in Lagoon.
      # @see https://github.com/uselagoon/lagoon/issues/1922
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 0 -S global || true

      # Override DB during re-deployment.
      if [ "${DREVOPS_DEPLOY_LAGOON_ACTION}" = "deploy_override_db" ]; then
        echo "  > Add a DB import override flag for the current deployment."
        # To update variable value, we need to remove it and add again.
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 1 -S global || true
      fi

      echo "  > Redeploying environment: project ${DREVOPS_DEPLOY_LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_LAGOON_BRANCH}."
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" deploy latest -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" || true

      if [ "${DREVOPS_DEPLOY_LAGOON_ACTION}" = "deploy_override_db" ]; then
        echo "  > Waiting for deployment to be queued."
        sleep 10

        echo "  > Remove a DB import override flag for the current deployment."
        # Note that a variable will be read by Lagoon during queuing of the build.
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" delete variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB || true
        lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" add variable -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -e "${DREVOPS_DEPLOY_LAGOON_BRANCH}" -N DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB -V 0 -S global || true
      fi

    # Deployment of the fresh environment.
    else
      # If current branch deployments does not match a regex in Lagoon - it will filter it out and will not deploy.
      echo "  > Deploying environment: project ${DREVOPS_DEPLOY_LAGOON_PROJECT}, branch: ${DREVOPS_DEPLOY_LAGOON_BRANCH}."
      lagoon --force --skip-update-check -i "${DREVOPS_DEPLOY_LAGOON_SSH_FILE}" -l "${DREVOPS_DEPLOY_LAGOON_INSTANCE}" deploy branch -p "${DREVOPS_DEPLOY_LAGOON_PROJECT}" -b "${DREVOPS_DEPLOY_LAGOON_BRANCH}"
    fi
  fi
fi

echo "==> Finished LAGOON deployment."
