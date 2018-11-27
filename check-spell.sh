#!/usr/bin/env bash

cat docs/README.md | \
# Remove { } attributes.
sed -E 's/\{:([^\}]+)\}//g' | \
# Remove HTML.
sed -E 's/<([^<]+)>//g' | \
# Remove code blocks.
sed  -n '/^\`\`\`/,/^\`\`\`/ !p' | \
# Remove links.
sed -E 's/http(s)?:\/\/([^ ]+)//g' | \
aspell --lang=en --encoding=utf-8 --personal=./.aspell.en.pws list | tee /dev/stderr | [ $(wc -l) -eq 0 ]
