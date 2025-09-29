#!/usr/bin/env bash
##
# Lint markdown.
#
# Lint all markdown files except those in the docs directory.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

[ ! -f "${ROOT_DIR}/.vortex/tests/node_modules/.bin/markdownlint-cli2" ] && yarn --cwd="${ROOT_DIR}/.vortex/tests" install --frozen-lockfile

# Parse arguments to check for --fix flag
MARKDOWNLINT_ARGS=()
for arg in "$@"; do
  if [[ "$arg" == "--fix" ]]; then
    MARKDOWNLINT_ARGS+=("--fix")
  else
    MARKDOWNLINT_ARGS+=("$arg")
  fi
done

"${ROOT_DIR}/.vortex/tests/node_modules/.bin/markdownlint-cli2" \
  --config "${ROOT_DIR}/.vortex/tests/.markdownlint.yaml" \
  "${MARKDOWNLINT_ARGS[@]}" \
  "${ROOT_DIR}"/.vortex/README.md \
  "${ROOT_DIR}"/.vortex/CLAUDE.md \
  "${ROOT_DIR}"/docs/*.md \
  "${ROOT_DIR}"/CLAUDE.md \
  "${ROOT_DIR}"/README.md \
  "${ROOT_DIR}"/README.dist.md \
  "${ROOT_DIR}"/SECURITY.md
