#!/usr/bin/env bash
##
# Publish docs.
#
# How it works:
# - Configure git user name and email.
# - Configure SSH based on the deployment SSH file or it's fingerprint provided.
# - Clone remote repository (again) and add the built files into it.
# - Force-push the code to the remote repository.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The source directory.
DOCS_PUBLISH_SRC_DIR=${DOCS_PUBLISH_SRC_DIR?Specify the source directory to publish.}

# The remote repository URL to push the docs to.
DOCS_PUBLISH_REMOTE_URL="${DOCS_PUBLISH_REMOTE_URL?Specify the remote URL to push the docs to.}"

# The remote repository branch to push the docs to.
DOCS_PUBLISH_REMOTE_BRANCH="${DOCS_PUBLISH_REMOTE_BRANCH:-main}"

# The fingerprint of the SSH key file to use for the deployment.
DOCS_PUBLISH_SSH_FINGERPRINT="${DOCS_PUBLISH_SSH_FINGERPRINT:-}"

# The commit message to use for the deployment.
DOCS_PUBLISH_COMMIT_MESSAGE="${DOCS_PUBLISH_COMMIT_MESSAGE:-Updated by automation}"

# The git user name to use for the deployment.
DOCS_PUBLISH_GIT_NAME="${DOCS_PUBLISH_GIT_NAME:-"Deployment robot"}"

# The git user email to use for the deployment.
DOCS_PUBLISH_GIT_EMAIL="${DOCS_PUBLISH_GIT_EMAIL:-"deployer@example.com"}"

#-------------------------------------------------------------------------------

main() {
  [ "$(git config --global user.name)" = "" ] && echo "Configuring global git user name." && git config --global user.name "${DOCS_PUBLISH_GIT_NAME}"
  [ "$(git config --global user.email)" = "" ] && echo "Configuring global git user email." && git config --global user.email "${DOCS_PUBLISH_GIT_EMAIL}"

  echo "==> Started docs release."
  echo "    Source dir:        ${DOCS_PUBLISH_SRC_DIR}"
  echo "    Remote URL:        ${DOCS_PUBLISH_REMOTE_URL}"
  echo "    Remote branch:     ${DOCS_PUBLISH_REMOTE_BRANCH}"

  push_to_remote "${DOCS_PUBLISH_SRC_DIR}"

  echo "==> Finished release."
}

push_to_remote() {
  # Directory with files to push to remote.
  local src_dir="${1:-}"

  echo "Pushing ${src_dir} to remote."

  configure_ssh

  # Temp directory to use for the local repo.
  local repo_dir
  repo_dir="$(create_dir "/tmp/docs/deploy_repo")"
  git_clone_dir "${DOCS_PUBLISH_REMOTE_URL}" "${DOCS_PUBLISH_REMOTE_BRANCH}" "${repo_dir}"

  pushd "${repo_dir}" >/dev/null || exit 1

  cp -Rf "${src_dir}"/* "${repo_dir}"
  git add -A
  git commit -m "${DOCS_PUBLISH_COMMIT_MESSAGE}"
  git push origin "${DOCS_PUBLISH_REMOTE_BRANCH}" --force

  popd >/dev/null || exit 1
}

configure_ssh() {
  # Use custom deploy key if fingerprint is provided.
  if [ -n "${DOCS_PUBLISH_SSH_FINGERPRINT}" ]; then
    echo "Custom deployment key is provided."
    DOCS_PUBLISH_SSH_FILE="${DOCS_PUBLISH_SSH_FINGERPRINT//:/}"
    DOCS_PUBLISH_SSH_FILE="${HOME}/.ssh/id_rsa_${DOCS_PUBLISH_SSH_FILE//\"/}"
  fi

  if [ -f "${DOCS_PUBLISH_SSH_FILE-}" ]; then
    if ssh-add -l | grep -q "${DOCS_PUBLISH_SSH_FILE}"; then
      echo "SSH agent has ${DOCS_PUBLISH_SSH_FILE} key loaded."
    else
      echo "SSH agent does not have a required key loaded. Trying to load."
      # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
      ssh-add -D >/dev/null
      ssh-add "${DOCS_PUBLISH_SSH_FILE}"
    fi
  fi

  # Disable strict host key checking in CI.
  if [ -n "${CI:-}" ]; then
    mkdir -p "${HOME}/.ssh"
    echo -e "\nHost *\n\tStrictHostKeyChecking no\n\tUserKnownHostsFile /dev/null\n" >>"${HOME}/.ssh/config"
  fi
}

#
# Clone dir into the specified destination directory.
#
git_clone_dir() {
  local url="${1:-}"
  local branch="${2:-}"
  local dst_dir="${3:-}"

  # Check if remote branch exists
  exists=$(git ls-remote --heads "${url}" "${branch}")

  if [ -z "${exists}" ]; then
    echo "    Branch ${branch} does not exist. Creating..."
    git clone "${url}" "${dst_dir}"
    pushd "${dst_dir}" >/dev/null || exit 1
    git checkout -b "${branch}"
    popd >/dev/null || exit 1
  else
    echo "    Branch ${branch} exists. Cloning..."
    git clone -b "${branch}" "${url}" "${dst_dir}"
  fi
}

#
# Create directory.
#
create_dir() {
  local dir="${1:-"$(pwd)"}"
  rm -Rf "${dir}" >/dev/null 2>&1
  mkdir -p "${dir}"
  [ -d "${dir}" ] || {
    echo "ERROR: Failed to create directory ${dir}."
    exit 1
  }
  echo "${dir}"
}

main "$@"
