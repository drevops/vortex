#!/usr/bin/env bash
##
# Mirror code to another git branch by force-pushing.
#
# Currently, supports only mirroring withing the same repository.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Source branch name to mirror code.
DREVOPS_MIRROR_CODE_BRANCH_SRC="${DREVOPS_MIRROR_CODE_BRANCH_SRC:-$1}"

# Destination branch name to mirror code.
DREVOPS_MIRROR_CODE_BRANCH_DST="${DREVOPS_MIRROR_CODE_BRANCH_DST:-${2}}"

# Destination remote name.
DREVOPS_MIRROR_CODE_REMOTE_DST="${DREVOPS_MIRROR_CODE_REMOTE_DST:-origin}"

# Flag to push the branch.
DREVOPS_MIRROR_CODE_PUSH="${DREVOPS_MIRROR_CODE_PUSH:-}"

# Optional SSH key fingerprint to use for mirroring.
DREVOPS_MIRROR_CODE_SSH_FINGERPRINT="${DREVOPS_MIRROR_CODE_SSH_FINGERPRINT:-}"

# Email address of the user who will be committing to a remote repository.
DREVOPS_MIRROR_CODE_GIT_USER_NAME="${DREVOPS_MIRROR_CODE_GIT_USER_NAME:-"Deployment Robot"}"

# Name of the user who will be committing to a remote repository.
DREVOPS_MIRROR_CODE_GIT_USER_EMAIL="${DREVOPS_MIRROR_CODE_GIT_USER_EMAIL:-}"

# ------------------------------------------------------------------------------

echo "🤖 Started code mirroring."

# Check all required values.
[ -z "${DREVOPS_MIRROR_CODE_BRANCH_SRC}" ] && echo "Missing required value for DREVOPS_MIRROR_CODE_BRANCH_SRC." && exit 1
[ -z "${DREVOPS_MIRROR_CODE_BRANCH_DST}" ] && echo "Missing required value for DREVOPS_MIRROR_CODE_BRANCH_SRC_REMOTE." && exit 1
[ -z "${DREVOPS_MIRROR_CODE_REMOTE_DST}" ] && echo "Missing required value for DREVOPS_MIRROR_CODE_REMOTE_DST." && exit 1
[ -z "${DREVOPS_MIRROR_CODE_GIT_USER_NAME}" ] && echo "Missing required value for DREVOPS_MIRROR_CODE_USER_NAME." && exit 1
[ -z "${DREVOPS_MIRROR_CODE_GIT_USER_EMAIL}" ] && echo "Missing required value for DREVOPS_MIRROR_CODE_GIT_USER_EMAIL." && exit 1

# Configure global git settings, if they do not exist.
[ "$(git config --global user.name)" == "" ] && echo "🤖 Configuring global git user name." && git config --global user.name "${DREVOPS_MIRROR_CODE_GIT_USER_NAME}"
[ "$(git config --global user.email)" == "" ] && echo "🤖 Configuring global git user email." && git config --global user.email "${DREVOPS_MIRROR_CODE_GIT_USER_EMAIL}"

# Use custom deploy key if fingerprint is provided.
if [ -n "${DREVOPS_MIRROR_CODE_SSH_FINGERPRINT}" ]; then
  echo "🤖 Custom deployment key is provided."
  DREVOPS_MIRROR_CODE_SSH_FILE="${DREVOPS_MIRROR_CODE_SSH_FINGERPRINT//:}"
  DREVOPS_MIRROR_CODE_SSH_FILE="${HOME}/.ssh/id_rsa_${DREVOPS_MIRROR_CODE_SSH_FILE//\"}"
fi

[ ! -f "${DREVOPS_MIRROR_CODE_SSH_FILE}" ] && echo "ERROR: SSH key file ${DREVOPS_MIRROR_CODE_SSH_FILE} does not exist." && exit 1

if ssh-add -l | grep -q "${DREVOPS_MIRROR_CODE_SSH_FILE}"; then
  echo "🤖 SSH agent has ${DREVOPS_MIRROR_CODE_SSH_FILE} key loaded."
else
  echo "🤖 SSH agent does not have default key loaded. Trying to load."
  # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
  ssh-add -D > /dev/null
  ssh-add "${DREVOPS_MIRROR_CODE_SSH_FILE}"
fi

# Create a temp directory to copy source repository into to prevent changes to source.
SRC_TMPDIR=$(mktemp -d)

echo "🤖 Copying files from the source repository to ${SRC_TMPDIR}."
rsync -a --keep-dirlinks ./. "${SRC_TMPDIR}"
[ -n "${DREVOPS_DEBUG}" ] && tree -L 4 "${SRC_TMPDIR}"

# Move to the temp source repo directory.
pushd "${SRC_TMPDIR}" >/dev/null || exit 1

# Reset any changes that may have been introduced during the CI run.
git reset --hard

# Checkout the branch, but only if the current branch is not the same.
current_branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "${DREVOPS_MIRROR_CODE_BRANCH_SRC}" != "${current_branch}" ] ;then
  git checkout -b "${DREVOPS_MIRROR_CODE_BRANCH_SRC}" "${DREVOPS_MIRROR_CODE_REMOTE_DST}/${DREVOPS_MIRROR_CODE_BRANCH_SRC}"
fi

if [ "${DREVOPS_MIRROR_CODE_PUSH}" = "1" ]; then
  git push "${DREVOPS_MIRROR_CODE_REMOTE_DST}" "${DREVOPS_MIRROR_CODE_BRANCH_SRC}:${DREVOPS_MIRROR_CODE_BRANCH_DST}" --force
else
  echo "Would push to ${DREVOPS_MIRROR_CODE_BRANCH_SRC}"
fi

popd >/dev/null || exit 1

echo "🤖 Finished code mirroring."
