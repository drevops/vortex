#!/usr/bin/env sh
##
# Update docs.
#
# @usage
# cd scripts/drevops/docs && ./release.sh

# cd scripts/drevops/docs && ./release.sh version
#
# cd scripts/drevops/docs && ./release.sh version ./site
#
# shellcheck disable=SC2129

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Name of the version to considered to be the development.
VERSION_DEV="${VERSION_DEV:-dev}"

# Name of the version to considered to be the latest.
VERSION_LATEST="${VERSION_LATEST:-latest}"

# Name of the version to be released. Defaults to the development version.
VERSION="${1:-${VERSION_DEV}}"

# Directory with the source files.
SRC_DIR="${SRC_DIR:-/app}"

# The output directory to copy the built files to.
OUTPUT_DIR="${2:-./site}"

# Source repository URL.
SRC_REPO_URL="${SRC_REPO:-https://github.com/drevops/drevops_docs.git}"

# Source repository branch.
SRC_REPO_BRANCH="${SRC_REPO_BRANCH:-master}"

# Temporary directory.
TMP_DIR="${TMP_DIR:-/tmp/release}"

# Source directory.
REPO_DIR="${REPO_DIR:-${TMP_DIR}/src}"

# Directory to use for a "clean" codebase.
REPO_DIR_CLEAN="${REPO_DIR2:-${TMP_DIR}/src_clean}"

#-------------------------------------------------------------------------------

echo "Started release."

# Configure global git settings, if they do not exist. This is not used to push changes.
[ "$(git config --global user.name)" = "" ] && echo "Configuring global git user name." && git config --global user.name "Deployment robot"
[ "$(git config --global user.email)" = "" ] && echo "Configuring global git user email." && git config --global user.email "robot@example.com"

echo "==> Prepare source repo"
if [ ! -d "${SRC_REPO_URL}" ]; then
  echo "    Clone source repo into directory ${REPO_DIR}"
  git clone "${SRC_REPO_URL}" "${REPO_DIR}" || true
else
  echo "    Using existing source repo in directory ${REPO_DIR}"
fi

echo "    Create ${VERSION} branch."
git checkout -b "${VERSION}" || true

echo "    Remove all files from the repo directory to copy the latest files."
rm -Rf "${REPO_DIR:?}"/*

echo "==> Copy latest source files."
cp -Rf "${SRC_DIR}"/* "${REPO_DIR}"

curdir=$PWD
cd "${REPO_DIR}"
echo "==> Create ${VERSION} version."
# Always create the dev version.
echo "    Update development ${VERSION_DEV} version."
mike deploy --update-aliases "${VERSION_DEV}"
if [ "${VERSION}" != "${VERSION_DEV}" ]; then
  echo "    Create release ${VERSION} version."
  mike deploy "${VERSION}"
  echo "    Set ${VERSION} as the ${VERSION_LATEST} version."
  mike set-default "${VERSION}"
  mike alias --update-aliases "${VERSION}" "${VERSION_LATEST}"
fi
cd "$curdir"

echo "==> Clean up."
echo "    Prepare the clean repo directory."
rm -Rf "${REPO_DIR_CLEAN}" || true
echo "    Copy repo into the clean repo directory."
git clone "${REPO_DIR}" "${REPO_DIR_CLEAN}"
echo "    Switch to the 'gh-pages' branch."
git --git-dir="${REPO_DIR_CLEAN}/.git" --work-tree="${REPO_DIR_CLEAN}" checkout gh-pages

echo "==> Copy content to the output directory."
echo "    Prepare output directory."
mkdir -p "${OUTPUT_DIR}"
rm -Rf "${OUTPUT_DIR:?}"/*
echo "    Copy built files into the output directory."
cp -Rf "${REPO_DIR_CLEAN:?}"/* "${OUTPUT_DIR}"
echo "    Copy CNAME from the source directory into the output directory."
cp "${SRC_DIR}/CNAME" "${OUTPUT_DIR}" || true

echo "Finished release."
echo "    Version:        ${VERSION}"
echo "    REPO_DIR:       ${REPO_DIR}"
echo "    REPO_DIR_CLEAN: ${REPO_DIR_CLEAN}"
echo "    OUTPUT_DIR:     ${OUTPUT_DIR}"
