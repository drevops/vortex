#!/usr/bin/env bash
##
# Lint DrevOps scripts.
#

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

echo "==> # Self test the variables wrapping script in ${CUR_DIR}."
pushd "${CUR_DIR}/scripts/drevops/tests" >/dev/null || exit 1
rm -Rf /tmp/variables.unwrapped.sh
cp fixtures/variables.unwrapped.sh /tmp/variables.unwrapped.sh
if ./lint.variables.sh /tmp/variables.unwrapped.sh >/dev/null; then
  echo "Command succeeded, failing test."
  exit 1
fi

rm -Rf /tmp/variables.unwrapped.sh
cp fixtures/variables.unwrapped.sh /tmp/variables.unwrapped.sh
./lint.variables.sh /tmp/variables.unwrapped.sh 1 1>/dev/null
diff fixtures/variables.wrapped.sh /tmp/variables.unwrapped.sh
popd >/dev/null || exit 1

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
    if [[ ${file} != *"docker-compose.bats"* ]]; then
      if ! "${CUR_DIR}/scripts/drevops/tests/lint.variables.sh" "${file}"; then
        exit 1
      fi
    fi

    if ! LC_ALL=C.UTF-8 shellcheck -e SC1090,SC1091,SC2223,SC2016 "${file}"; then
      exit 1
    fi

    if ! LC_ALL=C.UTF-8 shfmt -i 2 -ci -s -d "${file}"; then
      exit 1
    fi
  fi
done
