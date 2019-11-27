#!/usr/bin/env bash
# shellcheck disable=SC2181,SC2016,SC2002
##
# Check spelling.
#

CUR_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

targets=()

targets+=(README.md)
targets+=(docs/README.md)

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
    aspell --lang=en --encoding=utf-8 --personal="${CUR_DIR}/.aspell.en.pws" list | tee /dev/stderr | [ "$(wc -l)" -eq 0 ]

    if  [ "$?" -ne 0 ]; then
      exit 1
    fi
  fi
done;


