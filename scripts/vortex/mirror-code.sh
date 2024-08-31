#!/usr/bin/env bash
##
# Mirror code to another git branch by force-pushing.
#
# Currently, supports only mirroring withing the same repository.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Source branch name to mirror code.
VORTEX_MIRROR_CODE_BRANCH_SRC="${VORTEX_MIRROR_CODE_BRANCH_SRC:-${1}}"

# Destination branch name to mirror code.
VORTEX_MIRROR_CODE_BRANCH_DST="${VORTEX_MIRROR_CODE_BRANCH_DST:-${2}}"

# Destination remote name.
VORTEX_MIRROR_CODE_REMOTE_DST="${VORTEX_MIRROR_CODE_REMOTE_DST:-origin}"

# Flag to push the branch.
VORTEX_MIRROR_CODE_PUSH="${VORTEX_MIRROR_CODE_PUSH:-}"

# SSH key fingerprint used to connect to a remote.
VORTEX_MIRROR_CODE_SSH_FINGERPRINT="${VORTEX_MIRROR_CODE_SSH_FINGERPRINT:-}"

# Default SSH file used if custom fingerprint is not provided.
VORTEX_MIRROR_CODE_SSH_FILE="${VORTEX_MIRROR_CODE_SSH_FILE:-}"

# Email address of the user who will be committing to a remote repository.
VORTEX_MIRROR_CODE_GIT_USER_NAME="${VORTEX_MIRROR_CODE_GIT_USER_NAME:-"Deployment Robot"}"

# Name of the user who will be committing to a remote repository.
VORTEX_MIRROR_CODE_GIT_USER_EMAIL="${VORTEX_MIRROR_CODE_GIT_USER_EMAIL:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in git rsync; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

info "Started code mirroring."

# Check all required values.
[ -z "${VORTEX_MIRROR_CODE_BRANCH_SRC}" ] && fail "Missing required value for VORTEX_MIRROR_CODE_BRANCH_SRC." && exit 1
[ -z "${VORTEX_MIRROR_CODE_BRANCH_DST}" ] && fail "Missing required value for VORTEX_MIRROR_CODE_BRANCH_SRC_REMOTE." && exit 1
[ -z "${VORTEX_MIRROR_CODE_REMOTE_DST}" ] && fail "Missing required value for VORTEX_MIRROR_CODE_REMOTE_DST." && exit 1
[ -z "${VORTEX_MIRROR_CODE_GIT_USER_NAME}" ] && fail "Missing required value for VORTEX_MIRROR_CODE_USER_NAME." && exit 1
[ -z "${VORTEX_MIRROR_CODE_GIT_USER_EMAIL}" ] && fail "Missing required value for VORTEX_MIRROR_CODE_GIT_USER_EMAIL." && exit 1

# Configure global git settings, if they do not exist.
[ "$(git config --global user.name)" == "" ] && note "Configuring global git user name." && git config --global user.name "${VORTEX_MIRROR_CODE_GIT_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && note "Configuring global git user email." && git config --global user.email "${VORTEX_MIRROR_CODE_GIT_USER_EMAIL}"

export VORTEX_SSH_PREFIX="MIRROR_CODE" && . ./scripts/vortex/setup-ssh.sh

# Create a temp directory to copy source repository into to prevent changes to source.
SRC_TMPDIR=$(mktemp -d)

note "Copying files from the source repository to ${SRC_TMPDIR}."
rsync -a --keep-dirlinks ./. "${SRC_TMPDIR}"
# @docs:skip
[ -n "${VORTEX_DEBUG}" ] && tree -L 4 "${SRC_TMPDIR}"

# Move to the temp source repo directory.
pushd "${SRC_TMPDIR}" >/dev/null || exit 1

# Reset any changes that may have been introduced during the CI run.
git reset --hard

# Checkout the branch, but only if the current branch is not the same.
current_branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "${VORTEX_MIRROR_CODE_BRANCH_SRC:-}" != "${current_branch}" ]; then
  git checkout -b "${VORTEX_MIRROR_CODE_BRANCH_SRC}" "${VORTEX_MIRROR_CODE_REMOTE_DST}/${VORTEX_MIRROR_CODE_BRANCH_SRC}"
fi

if [ "${VORTEX_MIRROR_CODE_PUSH:-}" = "1" ]; then
  git push "${VORTEX_MIRROR_CODE_REMOTE_DST}" "${VORTEX_MIRROR_CODE_BRANCH_SRC}:${VORTEX_MIRROR_CODE_BRANCH_DST}" --force
else
  note "Would push to ${VORTEX_MIRROR_CODE_BRANCH_SRC}"
fi

popd >/dev/null || exit 1

pass "Finished code mirroring."
