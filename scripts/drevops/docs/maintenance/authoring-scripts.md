# Authoring scripts

## Requirements

!!! note

    Please refer to [RFC2119](https://www.ietf.org/rfc/rfc2119.txt) for meaning of words MUST, SHOULD and MAY.

1. MUST adhere to [POSIX standard](https://en.wikipedia.org/wiki/POSIX).
2. MUST pass Shellcheck code analysis scan
3. MUST start with:
```bash
 #!/usr/bin/env bash
 ##
 # Action description that the script performs.
 #
 # More description and usage information with a last empty
 # comment line.
 #

 set -e
 [ -n "${DREVOPS_DEBUG}" ] && set -x
```
4. MUST list all variables with their default values and descriptions. i.e.:
```bash
# Deployment reference, such as a git SHA.
DREVOPS_NOTIFY_REF="${DREVOPS_NOTIFY_REF:-}"
```
5. MUST include a delimiter between variables and the script body preceded and
   followed by an empty line (3 lines in total):
```bash
# ------------------------------------------------------------------------------
```
6. SHOULD include formatting helper functions:
```bash
# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on
```
7. SHOULD include variable values checks with errors and early exist, i.e.:
```bash
[ -z "${DREVOPS_NOTIFY_REF}" ] && fail "Missing required value for DREVOPS_NOTIFY_REF" && exit 1
```
8. SHOULD include binaries checks if the script relies on them, i.e.:
```bash
command -v curl > /dev/null || ( fail "curl command is not available." && exit 1 )
```
9. MUST contain an `info` message about the start of the script body, e.g.:
```bash
info "Started GitHub notification for operation ${DREVOPS_NOTIFY_EVENT}"
```
8. MUST contain an `pass` message about the finish of the script body, e.g.:
```bash
pass "Finished GitHub notification for operation ${DREVOPS_NOTIFY_EVENT}"
```
9. MUST use uppercase global variables
10. MUST use lowercase local variables.
11. MUST use `DREVOPS_` prefix for variables, unless it is a known 3-rd party
    variable like `GITHUB_TOKEN` or `COMPOSER`.
12. MUST use script-specific prefix. I.e., for `notify.sh`, the variable to skip
    notifications should start with `DREVOPS_NOTIFY_`.
13. MAY rely on variables from the external scripts (not prefixed with a
    script-specific prefix), but MUST declare such variables in the header of
    the file.
14. MAY call other DrevOps scripts (discouraged), but MUST source them rather
    than creating a sub-process. This is to allow passing environment variables
    down the call stack.
10. SHOULD use `note` messages for informing about the script progress.

## Scaffold script

Use the scaffold script below to kick-start your custom DrevOps script.

```bash
{! maintenance/script-scaffold.sh !}
```
