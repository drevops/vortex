#!/usr/bin/env bash
##
# Lint Vortex Dockerfiles.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

[ ! -f "${ROOT_DIR}/.vortex/tests/vendor/bin/shellvar" ] && composer --working-dir="${ROOT_DIR}/.vortex/tests" install

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${ROOT_DIR}"/.docker \
    -type f \
    -name "*.dockerfile" \
    -not -path "*vendor*" -not -path "*node_modules*" \
    -print0
)

echo "==> Linting Vortex Dockerfiles in ${ROOT_DIR}."
for file in "${targets[@]}"; do
  # Temp script until shfmt implement the support for formatting variables.
  # @see https://github.com/mvdan/sh/issues/1029
  if ! "${ROOT_DIR}/.vortex/tests/vendor/bin/shellvar" lint "${file}"; then
    exit 1
  fi

  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    docker run --rm -i hadolint/hadolint <"${file}"
  fi
done
