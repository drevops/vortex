#!/usr/bin/env bash
##
# Assemble the multi-version documentation site.
#
# Snapshots this branch's docs as the current major's version and stages the
# other major's docs from its '{N}.x' branch. Docusaurus serves the snapshot at
# the bare '/docs' and the staged docs at '/docs/v{other}'.
#
# Everything is written to disposable, git-ignored locations: the tracked
# 'content/' is only ever read. Delete 'WORKSPACE_DIR', 'versioned_docs',
# 'versioned_sidebars' and 'versions.json' to return to a single-version build.
#
# @usage
# cd .vortex/docs && ./.utils/assemble-versioned-docs.sh

set -eu
set -o pipefail
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The major this branch ships. Its docs become the site's default version.
VORTEX_CURRENT_MAJOR="${VORTEX_CURRENT_MAJOR:-1}"

# Staging area for the docs the build reads. 'docusaurus.config.js' switches to
# it when it exists.
WORKSPACE_DIR=".docusaurus-versioned"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../" && pwd)"

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

case "${VORTEX_CURRENT_MAJOR}" in
  1) other_major=2 ;;
  2) other_major=1 ;;
  *)
    echo "ERROR: Invalid VORTEX_CURRENT_MAJOR='${VORTEX_CURRENT_MAJOR}'. Expected 1 or 2." >&2
    exit 1
    ;;
esac

git -C "${ROOT_DIR}" fetch origin "${other_major}.x" --depth=1 || {
  echo "ERROR: Failed to fetch the ${other_major}.x branch." >&2
  exit 1
}

git -C "${ROOT_DIR}" rev-parse --verify "origin/${other_major}.x" >/dev/null || {
  echo "ERROR: The ${other_major}.x branch does not exist." >&2
  exit 1
}

# Start from a known state so a re-run cannot snapshot a previous assembly.
rm -rf "${WORKSPACE_DIR}" versioned_docs versioned_sidebars versions.json
mkdir -p "${WORKSPACE_DIR}"

# Snapshot the current major from a copy, so 'docs:version' reads the branch's
# documentation without the tracked 'content/' being the staging area.
cp -R content "${WORKSPACE_DIR}/content"

yarn docusaurus docs:version "${VORTEX_CURRENT_MAJOR}.x"

rm -rf "${WORKSPACE_DIR}/content"
mkdir -p "${WORKSPACE_DIR}/content"

git -C "${ROOT_DIR}" archive "origin/${other_major}.x:.vortex/docs/content" | tar -x -C "${WORKSPACE_DIR}/content" || {
  echo "ERROR: Failed to extract content from ${other_major}.x." >&2
  exit 1
}

[ -n "$(ls -A "${WORKSPACE_DIR}/content")" ] || {
  echo "ERROR: The ${other_major}.x branch carries no documentation content." >&2
  exit 1
}

# Every branch authors its docs against the bare '/docs' mount, so an absolute
# link lands on the current major once the content is served at '/docs/v{other}'.
# Re-point those links at the major they were written for. A link that already
# names a version is left as authored, so the alternation skips '/v' followed by
# a digit.
find "${WORKSPACE_DIR}/content" -type f \( -name '*.md' -o -name '*.mdx' \) -exec \
  sed -E "${sed_opts[@]}" "s%\]\(/docs([)#?]|/[^v]|/v[^0-9])%](/docs/v${other_major}\1%g" {} +
