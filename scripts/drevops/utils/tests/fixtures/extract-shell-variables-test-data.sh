#!/usr/bin/env bash
##
# Test data file for variables extraction script.
#
# shellcheck disable=SC2034,SC2154

VAR1=
VAR2=val2

VAR3="${val3}"
VAR4=${val4}

VAR5=${val5:-abc}

VAR6=${val6:-$VAR5}
VAR7=${val7:-${VAR5}}

VAR8=val8

# Description with leading space.
VAR9=val9

#Description without a leading space.
VAR10="val10"

# Description without a leading space that goes on
# multiple lines and has a `VAR7`, `$VAR8`, $VAR9, VAR10 and VAR12 variable reference.
VAR11=val11

# Description without a leading space that goes on
# multiple lines.
#
# And has a comment with no content.
VAR12=val12

# Description without a leading space that goes on
# multiple lines.

# And has an empty line before it without a content.
VAR13=val13

VAR14=val14

VAR15=val16

VAR17=val17

VARENV1=valenv1

VARENV2=

# Comment from script.
VARENV3=valenv3

# Comment 2 from script without a leading space that goes on
# multiple lines.
 VARENV4=

#VAR20=
#VAR21=val21
