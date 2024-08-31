#!/usr/bin/env bash
##
# Deploy code via pushing an artifact to a remote git repository.
#
# @see https://github.com/drevops/git-artifact
#
# Deployment to remote git repositories allows to build the project code as
# an artifact in CI and then commit only required files to the destination
# repository.
#
# During deployment, the `.gitignore.artifact` file determines which files
# to exclude, using the `.gitignore` syntax. When preparing the artifact build,
# this file supersedes the existing `.gitignore`, and any specified files are
# excluded.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Remote repository to push code to.
VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE="${VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE:-}"

# Email address of the user who will be committing to a remote repository.
VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME="${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME:-"Deployment Robot"}"

# Name of the user who will be committing to a remote repository.
VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL="${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL:-}"

# Source of the code to be used for artifact building.
VORTEX_DEPLOY_ARTIFACT_SRC="${VORTEX_DEPLOY_ARTIFACT_SRC:-}"

# The root directory where the deployment script should run from. Defaults to
# the current directory.
VORTEX_DEPLOY_ARTIFACT_ROOT="${VORTEX_DEPLOY_ARTIFACT_ROOT:-$(pwd)}"

# Remote repository branch. Can be a specific branch or a token.
# @see https://github.com/drevops/git-artifact#token-support
VORTEX_DEPLOY_ARTIFACT_DST_BRANCH="${VORTEX_DEPLOY_ARTIFACT_DST_BRANCH:-[branch]}"

# Deployment log file name.
VORTEX_DEPLOY_ARTIFACT_LOG="${VORTEX_DEPLOY_ARTIFACT_LOG:-${VORTEX_DEPLOY_ARTIFACT_ROOT}/deployment_log.txt}"

# SSH key fingerprint used to connect to remote.
VORTEX_DEPLOY_SSH_FINGERPRINT="${VORTEX_DEPLOY_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_DEPLOY_SSH_FILE="${VORTEX_DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started ARTIFACT deployment."

# Check all required values.
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_DST_BRANCH}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_DST_BRANCH." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_SRC}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_SRC." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_ROOT}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_ROOT." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_LOG}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_LOG." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL}" ] && echo "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL." && exit 1

# Configure global git settings, if they do not exist.
[ "$(git config --global user.name)" = "" ] && note "Configuring global git user name." && git config --global user.name "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME}"
[ "$(git config --global user.email)" = "" ] && note "Configuring global git user email." && git config --global user.email "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL}"

export VORTEX_SSH_PREFIX="DEPLOY" && . ./scripts/vortex/setup-ssh.sh

note "Installing artifact builder."
composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:^0.7

# Try resolving absolute paths.
if command -v realpath >/dev/null 2>&1; then
  # Expand relative paths while also handling literal tilde expansion passed in
  # singe quotes. This addresses the case where the paths are passed directly
  # from YAML anchors as literal strings.
  # shellcheck disable=SC2116
  VORTEX_DEPLOY_ARTIFACT_ROOT="$(realpath "${VORTEX_DEPLOY_ARTIFACT_ROOT/#\~/${HOME}}")"
  # shellcheck disable=SC2116
  VORTEX_DEPLOY_ARTIFACT_SRC="$(realpath "${VORTEX_DEPLOY_ARTIFACT_SRC/#\~/${HOME}}")"
fi

# Copying git repo files meta file to the deploy code repo as it may not exist
# in deploy code source files.
cp -a "${VORTEX_DEPLOY_ARTIFACT_ROOT}"/.git "${VORTEX_DEPLOY_ARTIFACT_SRC}" || true
# Copying deployment .gitignore as it may not exist in deploy code source files.
cp -a "${VORTEX_DEPLOY_ARTIFACT_ROOT}"/.gitignore.artifact "${VORTEX_DEPLOY_ARTIFACT_SRC}" || true

note "Running artifact builder."
# Add --debug to debug any deployment issues.
"${HOME}/.composer/vendor/bin/git-artifact" "${VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE}" \
  --root="${VORTEX_DEPLOY_ARTIFACT_ROOT}" \
  --src="${VORTEX_DEPLOY_ARTIFACT_SRC}" \
  --branch="${VORTEX_DEPLOY_ARTIFACT_DST_BRANCH}" \
  --gitignore="${VORTEX_DEPLOY_ARTIFACT_SRC}"/.gitignore.artifact \
  --log="${VORTEX_DEPLOY_ARTIFACT_LOG}" \
  -vvv

pass "Finished ARTIFACT deployment."
