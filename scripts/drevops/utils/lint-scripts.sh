#!/usr/bin/env bash
##
# Lint DrevOps scripts.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

CUR_DIR="$(dirname "$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")")"

if [ -d "${CUR_DIR}/scripts/drevops/tests" ]; then
  echo "==> Linting installer script and tests."
  pushd "${CUR_DIR}/scripts/drevops/tests" >/dev/null || exit 1
  [ ! -f "vendor/bin/phpcs" ] && composer install
  vendor/bin/phpcs -s --standard=Drupal ../../../install.php
  vendor/bin/phpcs -s --standard=Drupal unit
  popd >/dev/null || exit 1
fi

targets=()
while IFS=  read -r -d $'\0'; do
    targets+=("$REPLY")
done < <(
  find \
    "${CUR_DIR}"/install.sh \
    "${CUR_DIR}"/scripts \
    "${CUR_DIR}"/.circleci \
    "${CUR_DIR}"/hooks/library \
    "${CUR_DIR}"/scripts/drevops/utils \
    "${CUR_DIR}"/scripts/drevops/tests/bats \
    -type f \
    \( -name "*.sh" -or -name "*.bash" -or -name "*.bats" \) \
    -print0
  )
targets+=("${CUR_DIR}/install")

echo "==> Linting DrevOps scripts and tests in ${CUR_DIR}."
for file in "${targets[@]}"; do
  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    if ! LC_ALL=C.UTF-8 shellcheck -e SC2223 "${file}"; then
      exit 1
    fi
  fi
done;
