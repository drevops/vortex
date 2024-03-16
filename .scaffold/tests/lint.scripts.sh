#!/usr/bin/env bash
##
# Lint Scaffold scripts.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

[ ! -f "${ROOT_DIR}/.scaffold/tests/vendor/bin/shellvar" ] && composer --working-dir="${ROOT_DIR}/.scaffold/tests" install

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${ROOT_DIR}"/scripts \
    "${ROOT_DIR}"/.circleci \
    "${ROOT_DIR}"/hooks/library \
    "${ROOT_DIR}"/.scaffold/docs \
    -type f \
    \( -name "*.sh" -or -name "*.bash" -or -name "*.bats" \) \
    -not -path "*vendor*" -not -path "*node_modules*" -not -path "*fixtures*" \
    -print0
)

echo "==> Linting Scaffold scripts and tests in ${ROOT_DIR}."
for file in "${targets[@]}"; do

  if [ -f "${file}" ]; then
    echo "Checking file ${file}"

    if ! "${ROOT_DIR}/.scaffold/tests/vendor/bin/shellvar" lint "${file}"; then
      exit 1
    fi

    if ! LC_ALL=C.UTF-8 shellcheck "${file}"; then
      exit 1
    fi

    if ! LC_ALL=C.UTF-8 shfmt -i 2 -ci -s -d "${file}"; then
      exit 1
    fi
  fi
done
