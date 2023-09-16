#!/usr/bin/env bash
##
# Lint Dockerfiles.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

CUR_DIR="$(dirname "$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")")"

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${CUR_DIR}"/.docker \
    -type f \
    -name "*.dockerfile" \
    -not -path "*vendor*" -not -path "*node_modules*" \
    -print0
)
targets+=("${CUR_DIR}/install")

echo "==> Linting DrevOps scripts and tests in ${CUR_DIR}."
for file in "${targets[@]}"; do
  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    docker run --rm -i hadolint/hadolint <"${file}"
  fi
done
