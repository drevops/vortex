#!/usr/bin/env bash
##
# Deploy via Lagoon CLI.
#
# @see https://github.com/amazeeio/lagoon-cli

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The Lagoon project to perform deployment for.
LAGOON_PROJECT="${LAGOON_PROJECT:-}"

# The Lagoon branch to deploy.
DEPLOY_BRANCH="${DEPLOY_BRANCH:-}"

# The PR number to deploy.
DEPLOY_PR="${DEPLOY_PR:-}"

# The PR head branch to deploy.
DEPLOY_PR_HEAD="${DEPLOY_PR_HEAD:-}"

# The PR base branch (the branch the PR is raised against). Defaults to 'develop'.
DEPLOY_PR_BASE_BRANCH="${DEPLOY_PR_BASE_BRANCH:-develop}"

# The Lagoon instance to interact with.
LAGOON_INSTANCE="${LAGOON_INSTANCE:-amazeeio}"

# SSH key fingerprint used to connect to remote. If not used, the currently
# loaded default SSH key (the key used for code checkout) will be used or
# deployment will fail with an error if the default SSH key is not loaded.
# In most cases, the default SSH key does not work (because it is a read-only
# key used by CircleCI to checkout code from git), so you should add another
# deployment key.
DEPLOY_SSH_FINGERPRINT="${DEPLOY_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DEPLOY_SSH_FILE="${DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Location of the Lagoon CLI binary.
LAGOON_BIN_PATH="${LAGOON_BIN_PATH:-/tmp}"

# Flag to force the installation of Lagoon CLI.
FORCE_INSTALL_LAGOON_CLI="${FORCE_INSTALL_LAGOON_CLI:-}"

# ------------------------------------------------------------------------------

echo "==> Started LAGOON deployment."

## Check all required values.
[ -z "${LAGOON_PROJECT}" ] && echo "Missing required value for LAGOON_PROJECT." && exit 1
[ -z "${DEPLOY_BRANCH}" ] && echo "Missing required value for DEPLOY_BRANCH." && exit 1

# Use custom deploy key if fingerprint is provided.
if [ -n "${DEPLOY_SSH_FINGERPRINT}" ]; then
  echo "==> Custom deployment key is provided."
  DEPLOY_SSH_FILE="${DEPLOY_SSH_FINGERPRINT//:}"
  DEPLOY_SSH_FILE="${HOME}/.ssh/id_rsa_${DEPLOY_SSH_FILE//\"}"
fi

[ ! -f "${DEPLOY_SSH_FILE}" ] && echo "ERROR: SSH key file ${DEPLOY_SSH_FILE} does not exist." && exit 1

if ssh-add -l | grep -q "${DEPLOY_SSH_FILE}"; then
  echo "==> SSH agent has ${DEPLOY_SSH_FILE} key loaded."
else
  echo "==> SSH agent does not have default key loaded. Trying to load."
  # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
  ssh-add -D > /dev/null
  ssh-add "${DEPLOY_SSH_FILE}"
fi

# Disable strict host key checking in CI.
[ -n "${CI}" ] && mkdir -p "${HOME}/.ssh/" && echo -e "\nHost *\n\tStrictHostKeyChecking no\n\tUserKnownHostsFile /dev/null\n" >> "${HOME}/.ssh/config"

if ! command -v lagoon >/dev/null || [ -n "${FORCE_INSTALL_LAGOON_CLI}" ]; then
  echo "==> Installing Lagoon CLI."
  curl -sL https://api.github.com/repos/amazeeio/lagoon-cli/releases/latest \
    | grep "browser_download_url" \
    | grep -i "$(uname -s)-amd64\"$" \
    | cut -d '"' -f 4 \
    | xargs curl -L -o "${LAGOON_BIN_PATH}/lagoon"
  chmod +x "${LAGOON_BIN_PATH}/lagoon"
  export PATH="${PATH}:${LAGOON_BIN_PATH}"
fi

if [ -n "${DEPLOY_PR}" ]; then
  # If PR deployments are not configured in Lagoon - it will filter it out and will not deploy.
  echo "  > Deploying environment: project ${LAGOON_PROJECT}, PR: ${DEPLOY_PR}."
  lagoon --force --skip-update-check -i "${DEPLOY_SSH_FILE}" -l "${LAGOON_INSTANCE}" deploy pullrequest -p "${LAGOON_PROJECT}" -n "${DEPLOY_PR}" --baseBranchName "${DEPLOY_PR_BASE_BRANCH}" -R "origin/${DEPLOY_PR_BASE_BRANCH}" -H "${DEPLOY_BRANCH}" -M "${DEPLOY_PR_HEAD}" -t "PR ${DEPLOY_PR}"
else
  # If current branch deployments does not match a regex in Lagoon - it will filter it out and will not deploy.
  echo "  > Deploying environment: project ${LAGOON_PROJECT}, branch: ${DEPLOY_BRANCH}."
  lagoon --force --skip-update-check -i "${DEPLOY_SSH_FILE}" -l "${LAGOON_INSTANCE}" deploy branch -p "${LAGOON_PROJECT}" -b "${DEPLOY_BRANCH}"
fi

echo "==> Finished LAGOON deployment."
