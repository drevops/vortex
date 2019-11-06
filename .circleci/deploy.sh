#!/usr/bin/env bash
##
# Deploy artifact.
#
# It is a good practice to create a separate Deployer user with own SSH key for
# every project.
#
# Add the following variables through CircleCI UI.
# DEPLOY_USER_NAME - name of the user who will be committing to a remote repository.
# DEPLOY_USER_EMAIL - email address of the user who will be committing to a remote repository.
# DEPLOY_REMOTE - remote repository to push artifact to.
# DEPLOY_PROCEED - if the deployment should proceed. Useful for testing of the CI config.
set -e

# Flag to actually proceed with deployment.
DEPLOY_PROCEED="${DEPLOY_PROCEED:-0}"

DEPLOY_SSH_FINGERPRINT="${DEPLOY_SSH_FINGERPRINT:-}"

[ -z "${DEPLOY_SSH_FINGERPRINT}" ] && echo "Missing required value for DEPLOY_SSH_FINGERPRINT" && exit 1

if [ "${DEPLOY_PROCEED}" == "0" ]; then
  echo "Skipping deployment" && exit 0
fi

# Configure SSH to configure git and SSH to connect to remote servers for deployment.
mkdir -p "${HOME}/.ssh/"
echo -e "Host *\n\tStrictHostKeyChecking no\n" > "${HOME}/.ssh/config"
DEPLOY_SSH_FILE="${DEPLOY_SSH_FINGERPRINT//:}"
DEPLOY_SSH_FILE="${HOME}/.ssh/id_rsa_${DEPLOY_SSH_FILE//\"}"
if [ -f "${DEPLOY_SSH_FILE}" ]; then
  echo "Found Deploy SSH key file ${DEPLOY_SSH_FILE}"
  ssh-add -D > /dev/null
  ssh-add "${DEPLOY_SSH_FILE}"
fi

export DEPLOY_SRC="/workspace/code"
export DEPLOY_ROOT="/app"
export DEPLOY_REPORT="/tmp/artifacts/deployment_report.txt"

# shellcheck disable=SC1091
source scripts/deploy.sh
