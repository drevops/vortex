#!/usr/bin/env bash
##
# Lint DrevOps Dockerfiles.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

[ ! -f "${ROOT_DIR}/.drevops/tests/vendor/bin/shell-var-lint" ] && composer --working-dir="${ROOT_DIR}/.drevops/tests" install

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

echo "==> Linting DrevOps Dockerfiles in ${ROOT_DIR}."
for file in "${targets[@]}"; do
  # Temp script until shfmt implement the support for formatting variables.
  # @see https://github.com/mvdan/sh/issues/1029
  if ! "${ROOT_DIR}/.drevops/tests/vendor/bin/shell-var-lint" "${file}"; then
    exit 1
  fi

  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    docker run --rm -i hadolint/hadolint <"${file}"
  fi
done
