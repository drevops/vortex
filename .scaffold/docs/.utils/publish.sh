#!/usr/bin/env bash
##
# Build and publish docs.
#
# This script is a wrapper around [mike](https://github.com/jimporter/mike).
# Mike uses the current repository directory as a source of the files to build.
# It builds files and puts them onto the `gh-pages` branch. This script allows
# to push the built files to a custom branch and repository based on the
# current branch or tag name.
#
# Version types:
# - canary: Most up-to-date development code.
# - latest: Latest release version.
# - 1.2.3: Release version. Could be the latest.
# - some-branch: Branch version.
#
# How it works:
# - Find out the version based on the branch or tag name.
# - Build the docs:
#   - Check out the remote repository locally. This may already contain some
#     versions from the previous deployments.
#   - Prepare combined repository:
#     - The *current* source files are checked out into a temp branch and this
#       branch is currently checked out.
#     - The `gh-pages` branch has all content from the remote repo.
#   - Start container with mkdocs.
#   - Copy the combined files into container.
#   - Run `mike deploy` with the identified version name. It will build
#     the documentation site.
#   - Copy out the built site from the container into the local output directory.
# - Push the code:
#   - Configure git user name and email.
#   - Configure SSH based on the deployment SSH file or it's fingerprint provided.
#   - Clone remote repository (again) and add the built files into it.
#   - Force-push the code to the remote repository.
#

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The remote repository URL to push the docs to.
DOCS_PUBLISH_REMOTE_URL="${DOCS_PUBLISH_REMOTE_URL?Specify the remote URL to push the docs to.}"

# The remote repository branch to push the docs to.
DOCS_PUBLISH_REMOTE_BRANCH="${DOCS_PUBLISH_REMOTE_BRANCH:-main}"

# The source branch to build the docs from.
DOCS_PUBLISH_SRC_BRANCH=${DOCS_PUBLISH_SRC_BRANCH:-}

# The source tag to build the docs from.
DOCS_PUBLISH_SRC_TAG=${DOCS_PUBLISH_SRC_TAG:-}

# The fingerprint of the SSH key file to use for the deployment.
DOCS_PUBLISH_SSH_FINGERPRINT="${DOCS_PUBLISH_SSH_FINGERPRINT:-}"

# The commit message to use for the deployment.
DOCS_PUBLISH_COMMIT_MESSAGE="${DOCS_PUBLISH_COMMIT_MESSAGE:-Updated by automation}"

# The canary source branch. Publishing of the docs from this branch will
# be considered as a canary release.
DOCS_PUBLISH_CANARY_BRANCH="${DOCS_PUBLISH_CANARY_BRANCH:-main}"

# Canary version name. Canary usually points to the latest commit in the
# canary branch ('main', 'develop' etc.).
DOCS_PUBLISH_VERSION_CANARY="${DOCS_PUBLISH_VERSION_CANARY:-canary}"

# The git user name to use for the deployment.
DOCS_PUBLISH_GIT_NAME="${DOCS_PUBLISH_GIT_NAME:-"Deployment robot"}"

# The git user email to use for the deployment.
DOCS_PUBLISH_GIT_EMAIL="${DOCS_PUBLISH_GIT_EMAIL:-"deployer@example.com"}"

#-------------------------------------------------------------------------------

main() {
  if [ -z "${DOCS_PUBLISH_SRC_BRANCH}" ] && [ -z "${DOCS_PUBLISH_SRC_TAG}" ]; then
    echo "ERROR: You must specify either branch or tag to publish the docs to."
    exit 1
  fi

  [ "$(git config --global user.name)" = "" ] && echo "Configuring global git user name." && git config --global user.name "${DOCS_PUBLISH_GIT_NAME}"
  [ "$(git config --global user.email)" = "" ] && echo "Configuring global git user email." && git config --global user.email "${DOCS_PUBLISH_GIT_EMAIL}"

  # Business logic to determine the version name.
  version=""
  version_is_latest=0
  if [ -n "${DOCS_PUBLISH_SRC_TAG}" ]; then
    # A tag, if provided, is considered to be the latest release version.
    export version="${DOCS_PUBLISH_SRC_TAG}"
    export version_is_latest=1
  else
    if [ "${DOCS_PUBLISH_SRC_BRANCH}" = "${DOCS_PUBLISH_CANARY_BRANCH}" ]; then
      version="${DOCS_PUBLISH_VERSION_CANARY}"
    else
      version="${DOCS_PUBLISH_SRC_BRANCH}"
    fi
    export version="${version/\//-}"
  fi

  echo "==> Started docs release."
  echo "    Remote URL:        ${DOCS_PUBLISH_REMOTE_URL}"
  echo "    Remote branch:     ${DOCS_PUBLISH_REMOTE_BRANCH}"
  echo "    Source branch:     ${DOCS_PUBLISH_SRC_BRANCH:-<none>}"
  echo "    Source tag:        ${DOCS_PUBLISH_SRC_TAG:-<none>}"
  echo "    Canary branch:     ${DOCS_PUBLISH_CANARY_BRANCH}"
  echo "    Version:           ${version}"
  echo "    Version is latest: ${version_is_latest}"

  output_dir="$(create_dir "/tmp/docs/out")"
  build "${version}" "${version_is_latest}" "${output_dir}"

  if [ -f "./CNAME" ]; then
    echo "Copy CNAME file from the source directory into the output directory."
    cp "./CNAME" "${output_dir}"
  fi

  push_to_remote "${output_dir}"

  echo "==> Finished release."
}

build() {
  local version="${1:-}"
  local version_is_latest="${2:-0}"
  local output_dir="${3:-}"

  local combined_repo_dir

  combined_repo_dir="$(create_dir "/tmp/docs/combined_repo")"
  create_combined_repo "${combined_repo_dir}"

  echo "Start container with mkdocs."
  docker compose -p docs down || true
  docker compose up -d --force-recreate

  echo "Copy combined dir into the container."
  docker compose cp "${combined_repo_dir}/." mkdocs:"/tmp/build"
  docker compose exec mkdocs git config --global --add safe.directory "/tmp/build"
  # The credentials below are only used to produce some intermediate commits
  # while building the docs. They are not used to push the code.
  docker compose exec mkdocs git config --global user.name "Deployment robot"
  docker compose exec mkdocs git config --global user.email "robot@example.com"

  miked() {
    docker compose exec -w "/tmp/build" mkdocs mike "$@"
  }

  version_count="$(miked list | wc -l)"
  echo "==> Found ${version_count} versions."

  echo "==> Create \"${version}\" version."
  miked deploy "${version}" >/dev/null 2>&1

  if [ "${version_count}" -eq 0 ]; then
    echo "==> Setting the initial published version as the default version."
    version_is_latest=1
  fi

  if [ "${version_is_latest}" = "1" ]; then
    echo "==> Set latest version."
    miked set-default "${version}" >/dev/null
    miked alias --update-aliases "${version}" latest
  fi

  echo "==> Switch to the 'gh-pages' branch in the container and copy built site to host."
  docker compose exec -w /tmp/build mkdocs git checkout gh-pages
  docker compose cp mkdocs:"/tmp/build/." "${output_dir}"
  rm -Rf "${output_dir}/.git" >/dev/null
}

create_combined_repo() {
  # We have to give Mike a repository where:
  # - The *current* source files are checked out into a temp branch and this branch is currently checked out.
  # - The `gh-pages` branch has all content from the remote repo.
  local combined_repo_dir="$1"

  echo "Copy current files into the default branch combined repo."
  cp -Rf "." "${combined_repo_dir}/" >/dev/null
  rm -Rf "${combined_repo_dir}/.git" >/dev/null

  pushd "${combined_repo_dir}" >/dev/null || exit 1

  git init --initial-branch temp-src
  git add -A >/dev/null
  git commit -m "temp commit" >/dev/null

  echo "Clone the source repo and copy files into the 'gh-pages' branch of combined repo."

  local src_dir
  src_dir="$(create_dir "/tmp/src")"
  git_clone_dir "${DOCS_PUBLISH_REMOTE_URL}" "${DOCS_PUBLISH_REMOTE_BRANCH}" "${src_dir}"
  rm -Rf "${src_dir}/.git" >/dev/null

  # Create an orphan branch and copy files into it so that there are no files
  # or commit from the "main" branch.
  git checkout --orphan gh-pages
  git reset --hard
  git clean -fd

  cp -Rf "${src_dir}/." "${combined_repo_dir}/" >/dev/null
  git add -A >/dev/null
  git commit -m "Initial gh-pages commit" >/dev/null

  git checkout temp-src

  popd >/dev/null || exit 1
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
