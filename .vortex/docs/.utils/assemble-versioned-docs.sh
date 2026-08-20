#!/usr/bin/env bash
##
# Assemble the multi-version documentation site.
#
# Snapshots this branch's 'content/' as the current major's version and replaces
# 'content/' with the other major's docs from its '{N}.x' branch. Docusaurus
# serves the snapshot at the bare '/docs' and 'content/' at '/docs/v{other}'.
#
# @usage
# cd .vortex/docs && ./.utils/assemble-versioned-docs.sh

set -eu
set -o pipefail
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The major this branch ships. Its docs become the site's default version.
VORTEX_CURRENT_MAJOR="${VORTEX_CURRENT_MAJOR:-1}"

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

yarn docusaurus docs:version "${VORTEX_CURRENT_MAJOR}.x"

git -C "${ROOT_DIR}" fetch origin "${other_major}.x" --depth=1 || {
  echo "ERROR: Failed to fetch the ${other_major}.x branch." >&2
  exit 1
}

git -C "${ROOT_DIR}" rev-parse --verify "origin/${other_major}.x" >/dev/null || {
  echo "ERROR: The ${other_major}.x branch does not exist." >&2
  exit 1
}

rm -rf content
mkdir -p content

# Extracted through an archive rather than checked out, so a local run leaves
# the index untouched and 'git restore content' returns to the branch.
git -C "${ROOT_DIR}" archive "origin/${other_major}.x:.vortex/docs/content" | tar -x -C content || {
  echo "ERROR: Failed to extract content from ${other_major}.x." >&2
  exit 1
}

[ -n "$(ls -A content)" ] || {
  echo "ERROR: The ${other_major}.x branch carries no documentation content." >&2
  exit 1
}

# Every branch authors its docs against the bare '/docs' mount, so an absolute
# link lands on the current major once the content is served at '/docs/v{other}'.
# Re-point those links at the major they were written for. A link that already
# names a version is left as authored, so the alternation skips '/v' followed by
# a digit.
find content -type f \( -name '*.md' -o -name '*.mdx' \) -exec \
  sed -E "${sed_opts[@]}" "s%\]\(/docs([)#?]|/[^v]|/v[^0-9])%](/docs/v${other_major}\1%g" {} +
