#!/usr/bin/env bash
##
# Deploy via pushing code to remote git repository.
#
# @see https://github.com/integratedexperts/robo-git-artefact
#
# Deployment to remote git repositories allows to build the project and all
# required artifacts in CI and then commit only required files to
# the destination repository. This makes applications fast and secure,
# because none of unnecessary code (such as development tools) are exposed
# to production environment.
#
# The deployment functionality resides in this separate script to allow
# (emergency) deployment from local machine in case if CI is not working.
#
# The list of files to ignore during deployment is controlled by a file
# .gitignore.deployment, which has .gitignore syntax. During code preparation
# for pushing to remote, this file effectively replaces existing .gitignore
# and all files that are ignored get removed.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Remote repository to push code to.
DEPLOY_GIT_REMOTE="${DEPLOY_GIT_REMOTE:-}"

# Email address of the user who will be committing to a remote repository.
DEPLOY_GIT_USER_NAME="${DEPLOY_GIT_USER_NAME:-"Deployer Robot"}"

# Name of the user who will be committing to a remote repository.
DEPLOY_GIT_USER_EMAIL="${DEPLOY_GIT_USER_EMAIL:-}"

# Source of the code to be used for artifact building.
DEPLOY_CODE_SRC="${DEPLOY_CODE_SRC:-}"

# The root directory where the deployment script should run from. Defaults to
# the current directory.
DEPLOY_CODE_ROOT="${DEPLOY_CODE_ROOT:-$(pwd)}"

# SSH key fingerprint used to connect to remote. If not used, the currently
# loaded default SSH key (the key used for code checkout) will be used or
# deployment will fail with an error if the default SSH key is not loaded.
# In most cases, the default SSH key does not work (because it is a read-only
# key used by CircleCI to checkout code from git), so you should add another
# deployment key.
DEPLOY_SSH_FINGERPRINT="${DEPLOY_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
DEPLOY_SSH_FILE="${DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# Deployment report file name.
DEPLOY_REPORT="${DEPLOY_REPORT:-${DEPLOY_CODE_ROOT}/deployment_report.txt}"

# Remote repository branch. Can be a specific branch or a token.
# @see https://github.com/integratedexperts/robo-git-artefact#token-support
DEPLOY_GIT_BRANCH="${DEPLOY_GIT_BRANCH:-[branch]}"

# ------------------------------------------------------------------------------

echo "==> Started CODE deployment."

# Check all required values.
[ -z "${DEPLOY_GIT_REMOTE}" ] && echo "Missing required value for DEPLOY_GIT_REMOTE." && exit 1
[ -z "${DEPLOY_GIT_BRANCH}" ] && echo "Missing required value for DEPLOY_GIT_BRANCH." && exit 1
[ -z "${DEPLOY_CODE_SRC}" ] && echo "Missing required value for DEPLOY_CODE_SRC." && exit 1
[ -z "${DEPLOY_CODE_ROOT}" ] && echo "Missing required value for DEPLOY_CODE_ROOT." && exit 1
[ -z "${DEPLOY_REPORT}" ] && echo "Missing required value for DEPLOY_REPORT." && exit 1
[ -z "${DEPLOY_GIT_USER_NAME}" ] && echo "Missing required value for DEPLOY_GIT_USER_NAME." && exit 1
[ -z "${DEPLOY_GIT_USER_EMAIL}" ] && echo "Missing required value for DEPLOY_GIT_USER_EMAIL." && exit 1

# Configure global git settings, if they do not exist.
[ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name." && git config --global user.name "${DEPLOY_GIT_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email." && git config --global user.email "${DEPLOY_GIT_USER_EMAIL}"

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

echo "==> Installing a package for code push deployment."
composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs --no-suggest integratedexperts/robo-git-artefact:^0.4

# Copying git repo files meta file to the deploy code repo as it may not exist
# in deploy code source files.
cp -a "${DEPLOY_CODE_ROOT}"/.git "${DEPLOY_CODE_SRC}" || true
# Copying deployment .gitignore as it may not exist in deploy code source files.
cp -a "${DEPLOY_CODE_ROOT}"/.gitignore.deployment "${DEPLOY_CODE_SRC}" || true

# Run code deployment using special helper package.
# Add --debug to debug any deployment issues.
"${HOME}/.composer/vendor/bin/robo" --ansi \
  --load-from "${HOME}/.composer/vendor/integratedexperts/robo-git-artefact/RoboFile.php" artefact "${DEPLOY_GIT_REMOTE}" \
  --root="${DEPLOY_CODE_ROOT}" \
  --src="${DEPLOY_CODE_SRC}" \
  --branch="${DEPLOY_GIT_BRANCH}" \
  --gitignore="${DEPLOY_CODE_SRC}"/.gitignore.deployment \
  --report="${DEPLOY_REPORT}" \
  --push

echo "==> Finished CODE deployment."
