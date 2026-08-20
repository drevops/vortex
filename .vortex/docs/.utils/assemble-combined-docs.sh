#!/usr/bin/env bash
##
# Assemble the combined documentation site, serving both majors from one build.
#
# Produces a complete, self-contained Docusaurus site in 'docs_combined/': this
# branch's documentation as the default version at '/docs', and the other
# major's '{N}.x' branch documentation at '/docs/v{N}' with its own assets at
# '/v{N}'. Everything the assembly writes stays inside 'docs_combined/', which
# is disposable - 'docs/' is only ever read.
#
# @usage
# cd .vortex && ./docs/.utils/assemble-combined-docs.sh

set -eu
set -o pipefail
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The major this branch ships. Its docs become the site's default version.
VORTEX_CURRENT_MAJOR="${VORTEX_CURRENT_MAJOR:-1}"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../" && pwd)"
DOCS_DIR="${ROOT_DIR}/.vortex/docs"
COMBINED_DIR="${ROOT_DIR}/.vortex/docs_combined"

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

rm -rf "${COMBINED_DIR}"
mkdir -p "${COMBINED_DIR}"

# The combined site is a copy of this one, so it builds with the same config,
# components and sidebars. Generated and installed directories are excluded and
# rebuilt inside it, so it depends on nothing outside itself.
rsync -a \
  --exclude '/node_modules' \
  --exclude '/build' \
  --exclude '/.docusaurus' \
  --exclude '/.docusaurus-dev' \
  --exclude '/.logs' \
  "${DOCS_DIR}/" "${COMBINED_DIR}/"

yarn --cwd="${COMBINED_DIR}" install --frozen-lockfile

# Snapshot this branch as the default version before 'content/' is handed over
# to the other major. 'VORTEX_DOCS_COMBINED' stays unset here: the snapshot the
# combined config expects does not exist until this command creates it.
yarn --cwd="${COMBINED_DIR}" docusaurus docs:version "${VORTEX_CURRENT_MAJOR}.x"

rm -rf "${COMBINED_DIR}/content"
mkdir -p "${COMBINED_DIR}/content"

git -C "${ROOT_DIR}" archive "origin/${other_major}.x:.vortex/docs/content" | tar -x -C "${COMBINED_DIR}/content" || {
  echo "ERROR: Failed to extract content from ${other_major}.x." >&2
  exit 1
}

[ -n "$(ls -A "${COMBINED_DIR}/content")" ] || {
  echo "ERROR: The ${other_major}.x branch carries no documentation content." >&2
  exit 1
}

# Both majors record their own demo videos and diagrams under the same
# 'static/img' names, so the other major's assets get their own '/v{other}'
# prefix instead of losing to this major's copies.
other_static_dir="${COMBINED_DIR}/static/v${other_major}"
mkdir -p "${other_static_dir}"

git -C "${ROOT_DIR}" archive "origin/${other_major}.x:.vortex/docs/static" | tar -x -C "${other_static_dir}" || {
  echo "ERROR: Failed to extract static assets from ${other_major}.x." >&2
  exit 1
}

[ -n "$(ls -A "${other_static_dir}")" ] || {
  echo "ERROR: The ${other_major}.x branch carries no static assets." >&2
  exit 1
}

# Every branch authors its docs against the bare '/docs' mount, so an absolute
# link lands on the current major once the content is served at '/docs/v{other}'.
# Re-point those links at the major they were written for. A link that already
# names a version is left as authored, so the alternation skips '/v' followed by
# a digit.
find "${COMBINED_DIR}/content" -type f \( -name '*.md' -o -name '*.mdx' \) -exec \
  sed -E "${sed_opts[@]}" "s%\]\(/docs([)#?]|/[^v]|/v[^0-9])%](/docs/v${other_major}\1%g" {} +

# Asset references are written against the bare static root for the same reason.
# Each directory the other major ships is remapped, so a new asset directory on
# that branch is carried over without editing this script. Markdown targets and
# JSX attribute values are the two forms an asset path appears in.
for asset_dir in "${other_static_dir}"/*/; do
  [ -d "${asset_dir}" ] || continue

  asset_name="$(basename "${asset_dir}")"

  find "${COMBINED_DIR}/content" -type f \( -name '*.md' -o -name '*.mdx' \) -exec \
    sed -E "${sed_opts[@]}" "s%([\"'(])/${asset_name}/%\1/v${other_major}/${asset_name}/%g" {} +
done

VORTEX_DOCS_COMBINED=1 VORTEX_CURRENT_MAJOR="${VORTEX_CURRENT_MAJOR}" yarn --cwd="${COMBINED_DIR}" run build

echo "Combined documentation site built at ${COMBINED_DIR}/build"
