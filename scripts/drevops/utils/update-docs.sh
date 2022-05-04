#!/usr/bin/env bash
##
# Update docs.
#
# shellcheck disable=SC2129

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

var_file=../docs/variables.md
#cat $var_file | tr '\n' '\r' | sed 's/## Variables list.*//'  | tr '\r' '\n' > ../docs/variables.md
sed "${sed_opts[@]}" '/## Variables list/,$d' "$var_file"

echo "## Variables list" >> $var_file
echo >> $var_file
./extract-shell-variables.php  -t -s --markdown=extract-shell-variables-template.md -e ./extract-shell-variables-excluded.txt -u "<NOT SET>" ../../../ >> $var_file
