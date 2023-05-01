#!/usr/bin/env bash
##
# Update docs.
#
# @usage
# cd scripts/drevops/docs && ./update-docs.sh
#
# shellcheck disable=SC2129

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

[ ! -d "./.utils/vendor" ] && composer --working-dir="./.utils" install

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

OUTPUT_FILE="./usage/variables.md"
sed "${sed_opts[@]}" '/## Variables list/,$d' "${OUTPUT_FILE}"

echo "## Variables list" >> "${OUTPUT_FILE}"
echo >> "${OUTPUT_FILE}"
./.utils/vendor/drevops/shell-variables-extractor/extract-shell-variables.php \
  -t -s \
  -l ./.utils/variables.ticks-included.txt \
  --filter-global \
  --markdown=./.utils/variables.template.md \
  -e ./.utils/variables.excluded.txt -u "UNDEFINED" \
  ../../../.env \
  ../../../.env.local.example \
  ./.utils/variables.extra.sh \
  .. >> $OUTPUT_FILE
