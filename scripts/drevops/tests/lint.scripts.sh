#!/usr/bin/env bash
##
# Lint DrevOps scripts.
#
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

CUR_DIR="$(dirname "$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")")"

if [ -d "${CUR_DIR}/scripts/drevops/installer/tests" ]; then
  echo "==> Linting installer script and tests."
  pushd "${CUR_DIR}/scripts/drevops/installer" >/dev/null || exit 1
  [ ! -f "vendor/bin/phpcs" ] && composer install
  composer lint
  popd >/dev/null || exit 1
fi

[ ! -f "${CUR_DIR}/scripts/drevops/tests/vendor/bin/shell-var-lint" ] && composer --working-dir="${CUR_DIR}/scripts/drevops/tests" install

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${CUR_DIR}"/scripts \
    "${CUR_DIR}"/.circleci \
    "${CUR_DIR}"/hooks/library \
    -type f \
    \( -name "*.sh" -or -name "*.bash" -or -name "*.bats" \) \
    -not -path "*vendor*" -not -path "*node_modules*" -not -path "*fixtures*" \
    -print0
)

echo "==> Linting DrevOps scripts and tests in ${CUR_DIR}."
for file in "${targets[@]}"; do

  if [ -f "${file}" ]; then
    echo "Checking file ${file}"

    # Temp script until shfmt implement the support for formatting variables.
    # @see https://github.com/mvdan/sh/issues/1029
    if ! "${CUR_DIR}/scripts/drevops/tests/vendor/bin/shell-var-lint" "${file}"; then
      exit 1
    fi

    if ! LC_ALL=C.UTF-8 shellcheck -e SC1090,SC1091,SC2223,SC2016 "${file}"; then
      exit 1
    fi

    if ! LC_ALL=C.UTF-8 shfmt -i 2 -ci -s -d "${file}"; then
      exit 1
    fi
  fi
done
