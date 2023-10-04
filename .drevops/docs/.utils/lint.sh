#!/usr/bin/env bash
##
# Check spelling.
#
# shellcheck disable=SC2181,SC2016,SC2002,SC2266,SC2015
# LCOV_EXCL_START

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

CUR_DIR="$(dirname "${BASH_SOURCE[0]}")"

DICTIONARY="${CUR_DIR}/.aspell.en.pws"

targets=()
while IFS= read -r -d $'\0'; do
  targets+=("${REPLY}")
done < <(
  find \
    "${CUR_DIR}/.." \
    -type f \
    \( -name "*.md" \) \
    -not -path "*vendor*" -not -path "*node_modules*" \
    -print0
)

echo -n "==> Validating dictionary."
if head -1 "${DICTIONARY}" | grep -q "personal_ws-1.1 en 28"; then
  echo "OK"
else
  echo "ERROR: invalid dictionary format"
  exit 1
fi

echo "==> Linting DrevOps documentation spelling."
for file in "${targets[@]}"; do
  if [ -f "${file}" ]; then
    echo "Checking file ${file}"

    cat "${file}" |
      # Remove { } attributes.
      sed -E 's/\{:([^\}]+)\}//g' |
      # Replace <br/> with a space.
      sed -E 's/<br \/>|<br\/>|<br>/ /g' |
      # Remove HTML.
      sed -E 's/<([^<]+)>//g' |
      # Remove code blocks.
      sed -n '/\`\`\`/,/\`\`\`/ !p' |
      # Remove inline code.
      sed -n '/\`/,/\`/ !p' |
      # Remove anchors.
      sed -E 's/\[.+\]\([^\)]+\)//g' |
      # Remove links.
      sed -E 's/http(s)?:\/\/([^ ]+)//g' |
      aspell --lang=en --encoding=utf-8 --personal="${DICTIONARY}" list | tee /dev/stderr | [ "$(wc -l)" -eq 0 ]

    if [ "$?" -ne 0 ]; then
      exit 1
    fi
  fi
done
