#!/usr/bin/env bash
##
# Update docs.
#
# @usage
# cd scripts/drevops/utils && ./update-docs.sh
#
# shellcheck disable=SC2129

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

var_file=../docs/variables.md
sed "${sed_opts[@]}" '/## Variables list/,$d' "$var_file"

echo "## Variables list" >> $var_file
echo >> $var_file
./extract-shell-variables.php -t -s -l extract-shell-variables-ticks-included.txt --filter-global --markdown=extract-shell-variables-template.md -e ./extract-shell-variables-excluded.txt -u "UNDEFINED" ../../../.env .. >> $var_file
