#!/usr/bin/env bash
# shellcheck disable=SC2181,SC2016,SC2002
##
# Check spelling.
#

set -e

CUR_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

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
    -print0
  )

targets+=(README.md)
targets+=(DEPLOYMENT.md)
targets+=(FAQs.md)
targets+=(ONBOARDING.md)
targets+=(README.md)

echo "==> Start checking spelling"
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
    aspell --lang=en --encoding=utf-8 --personal="${CUR_DIR}/scripts/drevops/.aspell.en.pws" list | tee /dev/stderr | [ "$(wc -l)" -eq 0 ]

    if  [ "$?" -ne 0 ]; then
      exit 1
    fi
  fi
done;
