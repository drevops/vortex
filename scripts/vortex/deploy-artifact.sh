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
VORTEX_DEPLOY_ARTIFACT_SSH_FINGERPRINT="${VORTEX_DEPLOY_ARTIFACT_SSH_FINGERPRINT:-${VORTEX_DEPLOY_SSH_FINGERPRINT:-}}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_DEPLOY_ARTIFACT_SSH_FILE="${VORTEX_DEPLOY_ARTIFACT_SSH_FILE:-${VORTEX_DEPLOY_SSH_FILE:-${HOME}/.ssh/id_rsa}}"

# Version of git-artifact to download.
VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_VERSION="${VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_VERSION:-1.4.0}"

# SHA256 checksum of the git-artifact binary.
VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_SHA256="${VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_SHA256:-1fa99ff2a6f8dc6c1a42bcfc87ce75d04b2eab375216b0e3195a0e3b51a47646}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started ARTIFACT deployment."

# shellcheck disable=SC2043
for cmd in curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available."
  exit 1
}; done

# Check all required values.
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_DST_BRANCH}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_DST_BRANCH." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_SRC}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_SRC." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_ROOT}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_ROOT." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_LOG}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_LOG." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME." && exit 1
[ -z "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL}" ] && fail "Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL." && exit 1

# Configure global git settings, if they do not exist.
[ "$(git config --global user.name)" = "" ] && task "Configuring global git user name." && git config --global user.name "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME}"
[ "$(git config --global user.email)" = "" ] && task "Configuring global git user email." && git config --global user.email "${VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL}"

export VORTEX_SSH_PREFIX="DEPLOY_ARTIFACT" && . ./scripts/vortex/setup-ssh.sh

task "Installing artifact builder."
curl -sS -L "https://github.com/drevops/git-artifact/releases/download/${VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_VERSION}/git-artifact" -o "${TMPDIR:-/tmp}"/git-artifact
if ! echo "${VORTEX_DEPLOY_ARTIFACT_GIT_ARTIFACT_SHA256}  ${TMPDIR:-/tmp}/git-artifact" | sha256sum -c; then
  fail "SHA256 checksum verification failed for git-artifact binary."
  exit 1
fi
chmod +x "${TMPDIR:-/tmp}"/git-artifact

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

task "Copying git repo files meta file to the deploy code repo."
cp -a "${VORTEX_DEPLOY_ARTIFACT_ROOT}"/.git "${VORTEX_DEPLOY_ARTIFACT_SRC}" || true
task "Copying deployment .gitignore as it may not exist in deploy code source files."
cp -a "${VORTEX_DEPLOY_ARTIFACT_ROOT}"/.gitignore.artifact "${VORTEX_DEPLOY_ARTIFACT_SRC}" || true

task "Running artifact builder."
# Add --debug to debug any deployment issues.
"${TMPDIR:-/tmp}"/git-artifact "${VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE}" \
  --root="${VORTEX_DEPLOY_ARTIFACT_ROOT}" \
  --src="${VORTEX_DEPLOY_ARTIFACT_SRC}" \
  --branch="${VORTEX_DEPLOY_ARTIFACT_DST_BRANCH}" \
  --gitignore="${VORTEX_DEPLOY_ARTIFACT_SRC}"/.gitignore.artifact \
  --log="${VORTEX_DEPLOY_ARTIFACT_LOG}" \
  -vvv

pass "Finished ARTIFACT deployment."
