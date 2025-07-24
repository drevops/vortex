#!/usr/bin/env bash
##
# Update Vortex.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Re-export variables only from .env to ignore any local overrides in .env.local.
# shellcheck disable=SC1091
set -a && . ./.env && set +a

# Vortex remote or local repo URI, optionally including reference.
#
# Examples:
# https://github.com/drevops/vortex.git         # Will auto-discover the latest stable tag.
# https://github.com/drevops/vortex.git@stable  # Will auto-discover the latest stable tag.
# /local/path/to/vortex
# /local/path/to/vortex@stable
# /local/path/to/vortex@ref
VORTEX_INSTALL_REPO="${VORTEX_INSTALL_REPO:-${1:-}}"

# The URL of the installer script.
VORTEX_INSTALLER_URL="${VORTEX_INSTALLER_URL:-https://www.vortextemplate.com/install}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

export VORTEX_INSTALL_REPO
export VORTEX_INSTALLER_URL

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

curl -L "${VORTEX_INSTALLER_URL}"?"$(date +%s)" >/tmp/install
php /tmp/install --no-interaction
