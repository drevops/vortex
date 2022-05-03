#!/usr/bin/env bash
##
# Test file for variables extraction script.
#
# ./extract-shell-variables-test.sh
#
# shellcheck disable=SC2181,SC2016

echo "==> Test 1: extract all variables"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines."
VAR12;val12;"Description without a leading space that goes on multiple lines. And has a comment with no content."
VAR13;val13;"And has an empty line before it without a content."
VAR14;val14;
VAR15;val16;
VAR17;val17;
VAR2;val2;
VAR3;val3;
VAR4;val4;
VAR5;abc;
VAR6;VAR5;
VAR7;VAR5;
VAR8;val8;
VAR9;val9;"Description with leading space."'

actual="$(../extract-shell-variables.php --filter-global extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"


echo "==> Test 2: filter-out variables by exclude file"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines."
VAR12;val12;"Description without a leading space that goes on multiple lines. And has a comment with no content."
VAR13;val13;"And has an empty line before it without a content."
VAR15;val16;
VAR2;val2;
VAR3;val3;
VAR4;val4;
VAR5;abc;
VAR6;VAR5;
VAR7;VAR5;
VAR8;val8;
VAR9;val9;"Description with leading space."'

actual="$(../extract-shell-variables.php -e extract-shell-variables-test-data-excluded.txt --filter-global extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test 3: filter-out variables by prefix"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines."
VAR12;val12;"Description without a leading space that goes on multiple lines. And has a comment with no content."
VAR13;val13;"And has an empty line before it without a content."
VAR15;val16;
VAR2;val2;
VAR3;val3;
VAR4;val4;
VAR5;abc;
VAR6;VAR5;
VAR7;VAR5;
VAR8;val8;
VAR9;val9;"Description with leading space."'

actual="$(../extract-shell-variables.php -e extract-shell-variables-test-data-excluded.txt --filter-prefix=VAR1 --filter-global extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test 4: with ticks"
expected='Name;"Default value";Description
`VAR1`;`<UNSET>`;
`VAR10`;`val10`;"Description without a leading space."
`VAR11`;`val11`;"Description without a leading space that goes on multiple lines."
`VAR12`;`val12`;"Description without a leading space that goes on multiple lines. And has a comment with no content."
`VAR13`;`val13`;"And has an empty line before it without a content."
`VAR14`;`val14`;
`VAR15`;`val16`;
`VAR17`;`val17`;
`VAR2`;`val2`;
`VAR3`;`val3`;
`VAR4`;`val4`;
`VAR5`;`abc`;
`VAR6`;`VAR5`;
`VAR7`;`VAR5`;
`VAR8`;`val8`;
`VAR9`;`val9`;"Description with leading space."'

actual="$(../extract-shell-variables.php -t --filter-global extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"
