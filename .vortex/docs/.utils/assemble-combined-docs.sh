#!/usr/bin/env bash
##
# Assemble the combined documentation site, serving both majors from one build.
#
# Produces a complete, self-contained Docusaurus site in 'docs_combined/'. The
# current major is served at '/docs' with its assets at '/img'; the other major
# is served at '/docs/v{other}' with its assets at '/v{other}/img'.
#
# This checkout supplies the documentation for the major it ships
# ('VORTEX_DOCS_MAJOR'); the other major is read from its own branch. So a
# branch off 'main' is built as the current major and '{other}.x' is fetched,
# while a branch off '{other}.x' is built as the other major and 'main' is
# fetched. Everything the assembly writes stays inside 'docs_combined/', which
# is disposable - 'docs/' is only ever read.
#
# @usage
# cd .vortex && ./docs/.utils/assemble-combined-docs.sh

set -eu
set -o pipefail
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The major served as the site's default version at the bare '/docs'.
VORTEX_CURRENT_MAJOR="${VORTEX_CURRENT_MAJOR:-1}"

# The major this checkout ships. Defaults to the current major, which is the
# major that lives on the default branch.
VORTEX_DOCS_MAJOR="${VORTEX_DOCS_MAJOR:-${VORTEX_CURRENT_MAJOR}}"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../" && pwd)"
DOCS_DIR="${ROOT_DIR}/.vortex/docs"
COMBINED_DIR="${ROOT_DIR}/.vortex/docs_combined"

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

for major in "${VORTEX_CURRENT_MAJOR}" "${VORTEX_DOCS_MAJOR}"; do
  case "${major}" in
    1 | 2) ;;
    *)
      echo "ERROR: Invalid major '${major}'. Expected 1 or 2." >&2
      exit 1
      ;;
  esac
done

case "${VORTEX_CURRENT_MAJOR}" in
  1) other_major=2 ;;
  2) other_major=1 ;;
esac

# The current major lives on the default branch; every other major lives on its
# own '{N}.x' branch. Whichever side this checkout does not supply is fetched.
if [ "${VORTEX_DOCS_MAJOR}" = "${VORTEX_CURRENT_MAJOR}" ]; then
  default_ref=""
  other_ref="origin/${other_major}.x"
else
  default_ref="origin/main"
  other_ref=""
fi

fetch_ref="${default_ref}${other_ref}"
fetch_branch="${fetch_ref#origin/}"

git -C "${ROOT_DIR}" fetch origin "${fetch_branch}" --depth=1 || {
  echo "ERROR: Failed to fetch the ${fetch_branch} branch." >&2
  exit 1
}

git -C "${ROOT_DIR}" rev-parse --verify "${fetch_ref}" >/dev/null || {
  echo "ERROR: The ${fetch_branch} branch does not exist." >&2
  exit 1
}

##
# Replace a directory with a major's documentation tree.
#
# $1 - directory to populate.
# $2 - subdirectory of the documentation to read ('content' or 'static').
# $3 - git ref to read from, or empty to read this checkout.
##
populate() {
  local dest="${1}"
  local tree="${2}"
  local ref="${3}"

  rm -rf "${dest}"
  mkdir -p "${dest}"

  if [ -z "${ref}" ]; then
    cp -R "${DOCS_DIR}/${tree}/." "${dest}/"
  else
    git -C "${ROOT_DIR}" archive "${ref}:.vortex/docs/${tree}" | tar -x -C "${dest}"
  fi

  [ -n "$(ls -A "${dest}")" ] || {
    echo "ERROR: No '${tree}' found in ${ref:-this checkout}." >&2
    exit 1
  }
}

##
# Materialise the repository files a major's documentation reads verbatim.
#
# A page pulls a shipped file in with '!!raw-loader!@site/../../<path>', which
# resolves against the checkout the build runs in rather than the branch the
# page came from. The two majors do not ship the same files, so each major's
# targets are extracted from its own ref and the imports are re-pointed at
# those copies.
#
# $1 - directory holding the major's documentation.
# $2 - git ref the documentation came from, or empty to read this checkout.
# $3 - subdirectory of the combined site to hold the extracted files.
##
externalise() {
  local dir="${1}"
  local ref="${2}"
  local tag="${3}"
  local target="${COMBINED_DIR}/_external/${tag}"

  local paths
  paths="$(grep -rhoE "raw-loader!@site/\.\./\.\./[^']+" "${dir}" 2>/dev/null | sed -E 's%raw-loader!@site/\.\./\.\./%%' | sort -u)"

  [ -n "${paths}" ] || return 0

  mkdir -p "${target}"

  local path
  while IFS= read -r path; do
    [ -n "${path}" ] || continue

    mkdir -p "${target}/$(dirname "${path}")"

    if [ -z "${ref}" ]; then
      cp "${ROOT_DIR}/${path}" "${target}/${path}"
    else
      git -C "${ROOT_DIR}" show "${ref}:${path}" >"${target}/${path}"
    fi
  done <<EOF
${paths}
EOF

  find "${dir}" -type f \( -name '*.md' -o -name '*.mdx' \) -exec \
    sed -E "${sed_opts[@]}" "s%raw-loader!@site/\.\./\.\./%raw-loader!@site/_external/${tag}/%g" {} +
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

# Snapshot the current major, whose assets stay at the bare static root.
populate "${COMBINED_DIR}/content" content "${default_ref}"
populate "${COMBINED_DIR}/static" static "${default_ref}"
externalise "${COMBINED_DIR}/content" "${default_ref}" "v${VORTEX_CURRENT_MAJOR}"

# 'VORTEX_DOCS_COMBINED' stays unset here: the snapshot the combined config
# expects does not exist until this command creates it.
yarn --cwd="${COMBINED_DIR}" docusaurus docs:version "${VORTEX_CURRENT_MAJOR}.x"

# Hand 'content/' over to the other major. Both majors record their own demo
# videos and diagrams under the same 'static/img' names, so the other major's
# assets get their own '/v{other}' prefix instead of losing to the current
# major's copies.
other_static_dir="${COMBINED_DIR}/static/v${other_major}"

# The other major's binary is built into 'static/v{other}' before the assembly
# runs and is not tracked on any branch, so replacing that directory with the
# branch's own static tree would discard it.
staged_other_install="${DOCS_DIR}/static/v${other_major}/install"
[ -f "${staged_other_install}" ] || staged_other_install=""

populate "${COMBINED_DIR}/content" content "${other_ref}"
populate "${other_static_dir}" static "${other_ref}"
externalise "${COMBINED_DIR}/content" "${other_ref}" "v${other_major}"

[ -z "${staged_other_install}" ] || cp "${staged_other_install}" "${other_static_dir}/install"

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
