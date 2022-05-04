#!/usr/bin/env bash
##
# Test file for variables extraction script.
#
# ./extract-shell-variables-test.sh
#
# shellcheck disable=SC2181,SC2016

echo "==> Test: extract all variables"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines and has a `VAR7`, `$VAR8`, $VAR9, VAR10 and VAR12 variable reference."
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
VAR9;val9;"Description with leading space."
VARENV1;valenv1;
VARENV2;<UNSET>;
VARENV3;valenv3;"Comment from script."
VARENV4;<UNSET>;"Comment 2 from script without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php --filter-global fixtures/extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test: filter-out variables by exclude file"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines and has a `VAR7`, `$VAR8`, $VAR9, VAR10 and VAR12 variable reference."
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
VAR9;val9;"Description with leading space."
VARENV1;valenv1;
VARENV2;<UNSET>;
VARENV3;valenv3;"Comment from script."
VARENV4;<UNSET>;"Comment 2 from script without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php -e fixtures/extract-shell-variables-test-data-excluded.txt --filter-global fixtures/extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test 3: filter-out variables by prefix"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11;"Description without a leading space that goes on multiple lines and has a `VAR7`, `$VAR8`, $VAR9, VAR10 and VAR12 variable reference."
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
VAR9;val9;"Description with leading space."
VARENV1;valenv1;
VARENV2;<UNSET>;
VARENV3;valenv3;"Comment from script."
VARENV4;<UNSET>;"Comment 2 from script without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php -e fixtures/extract-shell-variables-test-data-excluded.txt --filter-prefix=VAR1 --filter-global fixtures/extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test: with ticks"
expected='Name;"Default value";Description
`VAR1`;`<UNSET>`;
`VAR10`;`val10`;"Description without a leading space."
`VAR11`;`val11`;"Description without a leading space that goes on multiple lines and has a `$VAR7`, `$VAR8`, `$VAR9`, `$VAR10` and `$VAR12` variable reference."
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
`VAR9`;`val9`;"Description with leading space."
`VARENV1`;`valenv1`;
`VARENV2`;`<UNSET>`;
`VARENV3`;`valenv3`;"Comment from script."
`VARENV4`;`<UNSET>`;"Comment `2` from script without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php -t --filter-global fixtures/extract-shell-variables-test-data.sh)"
#echo "${expected}"
#echo "${actual}"
diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test: with ticks with slugs"
expected='Name;"Default value";Description
`VAR1`;`<UNSET>`;
`VAR10`;`val10`;"Description without a leading space."
`VAR11`;`val11`;"Description without a leading space that goes on multiple lines and has a [`$VAR7`](#var7), [`$VAR8`](#var8), [`$VAR9`](#var9), [`$VAR10`](#var10) and [`$VAR12`](#var12) variable reference."
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
`VAR9`;`val9`;"Description with leading space."
`VARENV1`;`valenv1`;
`VARENV2`;`<UNSET>`;
`VARENV3`;`valenv3`;"Comment from script."
`VARENV4`;`<UNSET>`;"Comment `2` from script without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php -t -s --filter-global fixtures/extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test: Extract all variables from a directory"
expected='Name;"Default value";Description
VAR1;<UNSET>;
VAR10;val10;"Description without a leading space."
VAR11;val11bash;"Description from bash without a leading space that goes on multiple lines."
VAR12;val12;"Description without a leading space that goes on multiple lines. And has a comment with no content."
VAR13;val13;"And has an empty line before it without a content."
VAR14;val14;
VAR15;val16;
VAR17;val17;
VAR2;val2bash;
VAR3;val3;
VAR4;val4;
VAR5;abc;
VAR6;VAR5;
VAR7;VAR5;
VAR8;val8;
VAR9;val9;"Description with leading space."
VARENV1;valenv1_dotenv;
VARENV2;<UNSET>;
VARENV3;valenv3-dotenv;"Comment from script."
VARENV4;<UNSET>;"Comment 2 from .env without a leading space that goes on multiple lines."'

actual="$(../extract-shell-variables.php --filter-global fixtures)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"

echo "==> Test: extract all variables into markdown blocks"
expected='### `VAR1`

Default value: `<UNSET>`

### `VAR10`

Description without a leading space.

Default value: `val10`

### `VAR11`

Description without a leading space that goes on multiple lines and has a `VAR7`, `$VAR8`, $VAR9, VAR10 and VAR12 variable reference.

Default value: `val11`

### `VAR12`

Description without a leading space that goes on multiple lines. And has a comment with no content.

Default value: `val12`

### `VAR13`

And has an empty line before it without a content.

Default value: `val13`

### `VAR14`

Default value: `val14`

### `VAR15`

Default value: `val16`

### `VAR17`

Default value: `val17`

### `VAR2`

Default value: `val2`

### `VAR3`

Default value: `val3`

### `VAR4`

Default value: `val4`

### `VAR5`

Default value: `abc`

### `VAR6`

Default value: `VAR5`

### `VAR7`

Default value: `VAR5`

### `VAR8`

Default value: `val8`

### `VAR9`

Description with leading space.

Default value: `val9`

### `VARENV1`

Default value: `valenv1`

### `VARENV2`

Default value: `<UNSET>`

### `VARENV3`

Comment from script.

Default value: `valenv3`

### `VARENV4`

Comment 2 from script without a leading space that goes on multiple lines.

Default value: `<UNSET>`'

actual="$(../extract-shell-variables.php --filter-global --markdown=fixtures/extract-shell-variables-test-template.md fixtures/extract-shell-variables-test-data.sh)"

diff --normal <(echo "${expected}" ) <(echo "${actual}")
[ "$?" = "0" ] && echo "  > OK" || echo  "  > Not OK"
