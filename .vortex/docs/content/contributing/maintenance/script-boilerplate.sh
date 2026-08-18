#!/usr/bin/env bash
##
# Action description that the script performs.
#
# More description and usage information with a last empty
# comment line.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Example Vortex variable with a default value.
VORTEX_EXAMPLE_URL="${VORTEX_EXAMPLE_URL:-http://example.com}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

info "Started Vortex operations."

[ -z "${VORTEX_EXAMPLE_URL}" ] && fail "Missing required value for VORTEX_EXAMPLE_URL."
command -v curl >/dev/null || fail "curl command is not available."

# Example of the script body. Every task is closed by a pass or a fail. The
# assignment is guarded so a curl transport error reports through fail instead
# of aborting the script via 'set -e' before fail can run.
task "Requesting example page."
if ! status="$(curl -L -s -o /dev/null -w "%{http_code}" "${VORTEX_EXAMPLE_URL}")"; then
  fail "Unable to reach ${VORTEX_EXAMPLE_URL}."
fi
echo "${status}" | grep -q '200\|403' || fail "Example page returned status ${status}."
pass "Requested example page."

pass "Finished Vortex operations."
