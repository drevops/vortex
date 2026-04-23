#!/usr/bin/env bash
##
# Lint Vortex scripts.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

[ ! -f "${ROOT_DIR}/.vortex/tests/vendor/bin/shellvar" ] && composer --working-dir="${ROOT_DIR}/.vortex/tests" install

# Mask out lines between '# @formatter:off' and '# @formatter:on' so that
# shfmt does not reformat helper functions that are intentionally kept on
# a single line. Each masked line is replaced with a ':' (no-op) placeholder
# indented to match the '# @formatter:off' line's own indentation, so the
# surrounding code parses as valid bash at the expected scope.
mask_protected() {
  awk '
    /# @formatter:off/ {
      in_block = 1
      match($0, /^[[:space:]]*/)
      indent = substr($0, 1, RLENGTH)
      print
      next
    }
    /# @formatter:on/ {
      in_block = 0
      print
      next
    }
    in_block { print indent ":"; next }
    { print }
  ' "${1}"
}

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${ROOT_DIR}"/scripts \
    "${ROOT_DIR}"/.circleci \
    "${ROOT_DIR}"/hooks/library \
    "${ROOT_DIR}"/.vortex/docs \
    "${ROOT_DIR}"/.vortex/tests/bats \
    "${ROOT_DIR}"/.vortex/tests/manual \
    -type f \
    \( -name "*.sh" -or -name "*.bash" -or -name "*.bats" \) \
    -not -path "*vendor*" -not -path "*node_modules*" -not -path "*fixtures*" \
    -print0
)

echo "==> Linting Vortex scripts and tests in ${ROOT_DIR}."
for file in "${targets[@]}"; do

  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    if ! LC_ALL=C.UTF-8 shellcheck "${file}"; then
      exit 1
    fi

    if ! mask_protected "${file}" | LC_ALL=C.UTF-8 shfmt -i 2 -ci -s -d; then
      exit 1
    fi
  fi
done
