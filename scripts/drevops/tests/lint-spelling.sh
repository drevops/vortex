#!/usr/bin/env bash
##
# Check spelling.
#
# shellcheck disable=SC2181,SC2016,SC2002,SC2266,SC2015

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

CUR_DIR="$(dirname "$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")")"

dict="${CUR_DIR}/scripts/drevops/tests/.aspell.en.pws"

targets=()
while IFS=  read -r -d $'\0'; do
    targets+=("$REPLY")
done < <(
  find \
    "${CUR_DIR}"/.circleci \
    "${CUR_DIR}"/.docker \
    "${CUR_DIR}"/scripts \
    "${CUR_DIR}"/patches \
    -type f \
    \( -name "*.md" \) \
    -not -path "*vendor*" -not -path "*node_modules*" \
    -print0
  )

targets+=(DEPLOYMENT.md)
targets+=(FAQs.md)
targets+=(ONBOARDING.md)
targets+=(README.md)

echo -n "==> Validating dictionary..." && cat "${dict}" | head -1 | grep -q "personal_ws-1.1 en 28" && echo "OK" || (echo "ERROR: invalid dictionary format" && exit 1)

echo "==> Start checking spelling."
for file in "${targets[@]}"; do
  if [ -f "${file}" ]; then
    echo "Checking file ${file}"

    cat "${file}" | \
    # Remove { } attributes.
    sed -E 's/\{:([^\}]+)\}//g' | \
    # Remove HTML.
    sed -E 's/<([^<]+)>//g' | \
    # Remove code blocks.
    sed  -n '/\`\`\`/,/\`\`\`/ !p' | \
    # Remove inline code.
    sed  -n '/\`/,/\`/ !p' | \
    # Remove anchors.
    sed -E 's/\[.+\]\([^\)]+\)//g' | \
    # Remove links.
    sed -E 's/http(s)?:\/\/([^ ]+)//g' | \
    aspell --lang=en --encoding=utf-8 --personal="${dict}" list | tee /dev/stderr | [ "$(wc -l)" -eq 0 ]

    if  [ "$?" -ne 0 ]; then
      exit 1
    fi
  fi
done;
