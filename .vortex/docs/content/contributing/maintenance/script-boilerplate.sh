#!/usr/bin/env bash
##
# Action description that the script performs.
#
# More description and usage information with a last empty
# comment line.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Example scaffold variable with a default value.
VORTEX_EXAMPLE_URL="${VORTEX_EXAMPLE_URL:-http://example.com}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started scaffold operations."

[ -z "${VORTEX_EXAMPLE_URL}" ] && fail "Missing required value for VORTEX_EXAMPLE_URL" && exit 1
command -v curl >/dev/null || (fail "curl command is not available." && exit 1)

# Example of the script body.
curl -L -s -o /dev/null -w "%{http_code}" "${VORTEX_EXAMPLE_URL}" | grep -q '200\|403' && note "Requested example page"

pass "Finished scaffold operations."
