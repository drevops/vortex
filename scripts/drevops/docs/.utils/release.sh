#!/usr/bin/env sh
##
# Build and release docs.
#
# Version types:
# - latest: Most up-to-date development code.
# - 1.2.3: Stable release version.
# - stable: Points to the latest stable release.
# - some-branch: Branch version.
#
# @usage
#
# # Release version as 'latest'.
# ./release.sh repo_url
#
# # Release a custom version named <version>.
# ./release.sh repo_url <version>
#
# # Release <version> as stable. This will also be accessible as 'stable'.
# ./release.sh repo_url <version> 1
#
# shellcheck disable=SC2129

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Source repository URL.
SRC_REPO_URL="${1?:You must specify the source repository URL.}"

# Name of the version. Defaults to 'latest'.
VERSION="${2:-latest}"

# Mark the version as stable.
VERSION_IS_STABLE="${VERSION_IS_STABLE:-${3:-0}}"

# Source repository branch.
SRC_REPO_BRANCH="${SRC_REPO_BRANCH:-main}"

# The output directory to copy the built files to.
OUTPUT_DIR="${OUTPUT_DIR:-./site}"

#-------------------------------------------------------------------------------

# Directory with the source files.
SRC_DIR="/app"

# Temporary directory.
tmp_dir="/tmp/release"

# Source directory.
repo_dir="${tmp_dir}/src"

# Directory to use for a "clean" codebase.
repo_dir_clean="${tmp_dir}/src_clean"

echo "==> Started release."

# Configure global git settings, if they do not exist. This is not used to push changes.
[ "$(git config --global user.name)" = "" ] && echo "Configuring global git user name." && git config --global user.name "Deployment robot"
[ "$(git config --global user.email)" = "" ] && echo "Configuring global git user email." && git config --global user.email "robot@example.com"

echo "==> Prepare source repo"
if [ ! -d "${repo_dir}" ]; then
  echo "    Clone source repo into directory ${repo_dir}"
  git clone -b "${SRC_REPO_BRANCH}" "${SRC_REPO_URL}" "${repo_dir}" || true
else
  echo "    Using existing source repo in directory ${repo_dir}"
fi

curdir=$PWD
cd "${repo_dir}"

echo "    Current branch:"
echo "    $(git rev-parse --abbrev-ref HEAD)"
echo

echo "    Create ${VERSION} branch."
git branch --delete "${VERSION}" >/dev/null 2>&1 || true
git checkout -b "${VERSION}" >/dev/null 2>&1 || true

echo "    Remove all files from the repo directory to copy the latest files."
rm -Rf "${repo_dir:?}"/*

echo "    Copy the latest source files."
cp -Rf "${SRC_DIR}"/* "${repo_dir}"

version_count="$(mike list | wc -l)"

echo "==> Create \"${VERSION}\" version."
mike deploy "${VERSION}" >/dev/null 2>&1

if [ "${version_count}" -eq 0 ]; then
  echo "==> Setting the initial published version as the default version."
  VERSION_IS_STABLE=1
fi

if [ "${VERSION_IS_STABLE}" = "1" ]; then
  echo "==> Set stable version."
  mike set-default "${VERSION}" >/dev/null
  mike alias --update-aliases "${VERSION}" stable
fi

cd "$curdir"

echo "==> Clean up."
echo "    Prepare the clean repo directory."
rm -Rf "${repo_dir_clean}" || true
echo "    Copy repo into the clean repo directory."
git clone "${repo_dir}" "${repo_dir_clean}"
echo "    Switch to the 'gh-pages' branch."
git --git-dir="${repo_dir_clean}/.git" --work-tree="${repo_dir_clean}" checkout gh-pages

echo "==> Copy content to the output directory."
echo "    Prepare output directory."
mkdir -p "${OUTPUT_DIR}"
rm -Rf "${OUTPUT_DIR:?}"/*
echo "    Copy built files into the output directory."
cp -Rf "${repo_dir_clean:?}"/* "${OUTPUT_DIR}"
echo "    Copy CNAME file from the source directory into the output directory."
cp "${SRC_DIR}/CNAME" "${OUTPUT_DIR}" || true

echo "==> Finished release."
echo "    Version:           ${VERSION}"
echo "    Version is stable: ${VERSION_IS_STABLE}"
echo "    REPO_DIR:          ${repo_dir}"
echo "    REPO_DIR_CLEAN:    ${repo_dir_clean}"
echo "    OUTPUT_DIR:        ${OUTPUT_DIR}"
