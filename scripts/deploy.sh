#!/usr/bin/env bash
##
# Deploy artefact.
#
# Deployment to remote git repositories allows to build the project and all
# required artifacts in CI and then commit only required files to
# the destination repository. This makes applications fast and secure,
# because none of unnecessary code (such as development tools) are  exposed
# to production environment.
set -e

# Remote repository to push artefact to.
DEPLOY_REMOTE="${DEPLOY_REMOTE:-}"
# Remote repository branch. Can be a specific branch or a token.
# @see https://github.com/integratedexperts/robo-git-artefact#token-support
DEPLOY_BRANCH="${DEPLOY_BRANCH:-[branch]}"
# Source of the code to be used for artefact building.
DEPLOY_SRC="${DEPLOY_SRC:-}"
# The root directory where the deployment script should run from. Defaults to
# the current directory.
DEPLOY_ROOT="${DEPLOY_ROOT:-$(pwd)}"
# Deployment report file name.
DEPLOY_REPORT="${DEPLOY_REPORT:-${DEPLOY_ROOT}/deployment_report.txt}"
# Email address of the user who will be committing to a remote repository.
DEPLOY_USER_NAME="${DEPLOY_USER_NAME:-"Deployer Robot"}"
# Name of the user who will be committing to a remote repository.
DEPLOY_USER_EMAIL="${DEPLOY_USER_EMAIL:-deployer@your-site-url}"

[ -z "${DEPLOY_REMOTE}" ] && echo "Missing required value for DEPLOY_REMOTE" && exit 1
[ -z "${DEPLOY_BRANCH}" ] && echo "Missing required value for DEPLOY_BRANCH" && exit 1
[ -z "${DEPLOY_SRC}" ] && echo "Missing required value for DEPLOY_SRC" && exit 1
[ -z "${DEPLOY_ROOT}" ] && echo "Missing required value for DEPLOY_ROOT" && exit 1
[ -z "${DEPLOY_REPORT}" ] && echo "Missing required value for DEPLOY_REPORT" && exit 1
[ -z "${DEPLOY_USER_NAME}" ] && echo "Missing required value for DEPLOY_USER_NAME" && exit 1
[ -z "${DEPLOY_USER_EMAIL}" ] && echo "Missing required value for DEPLOY_USER_EMAIL" && exit 1

[ "$(git config --global user.name)" == "" ] && echo "==> Configuring global git user name" && git config --global user.name "${DEPLOY_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && echo "==> Configuring global git user email" && git config --global user.email "${DEPLOY_USER_EMAIL}"

echo "==> Installing a package for deployment."
composer require --dev -n --ansi --prefer-source --ignore-platform-reqs integratedexperts/robo-git-artefact

cp -a "${DEPLOY_ROOT}"/.git "${DEPLOY_SRC}"
cp -a "${DEPLOY_ROOT}"/.gitignore.deployment "${DEPLOY_SRC}"

vendor/bin/robo --ansi \
  --load-from vendor/integratedexperts/robo-git-artefact/RoboFile.php artefact "${DEPLOY_REMOTE}" \
  --root="${DEPLOY_ROOT}" \
  --src="${DEPLOY_SRC}" \
  --branch="${DEPLOY_BRANCH}" \
  --gitignore="${DEPLOY_SRC}"/.gitignore.deployment \
  --report="${DEPLOY_REPORT}" \
  --debug \
  -vvvv \
  --push
