#!/usr/bin/env bash
##
# Lint DrevOps scripts.
#

CUR_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

targets=()
while IFS=  read -r -d $'\0'; do
    targets+=("$REPLY")
done < <(
  find \
    "${CUR_DIR}"/install.sh \
    "${CUR_DIR}"/scripts \
    "${CUR_DIR}"/.circleci \
    "${CUR_DIR}"/tests/bats \
    -type f \
    \( -name "*.sh" -or -name "*.bash" \) \
    -print0
  )
targets+=("${CUR_DIR}/install")

echo "==> Start linting scripts in ${CUR_DIR}"
for file in "${targets[@]}"; do
  if [ -f "${file}" ]; then
    echo "Checking file ${file}"
    if ! LC_ALL=C.UTF-8 shellcheck "${file}"; then
      exit 1
    fi
  fi
done;
