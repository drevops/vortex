#!/usr/bin/env bash
##
# Update Vortex from the template repository.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Vortex remote or local template repo URI, optionally including reference.
#
# Examples:
# https://github.com/drevops/vortex.git          # Will auto-discover the latest stable tag from remote repo.
# https://github.com/drevops/vortex.git@stable   # Will auto-discover the latest stable tag from remote repo.
# https://github.com/drevops/vortex.git@1.2.3    # Will use specific release from remote repo.
# https://github.com/drevops/vortex.git@abcd123  # Will use specific commit from remote repo.
# file:///local/path/to/vortex.git               # Will auto-discover the latest stable tag from local repo.
# file:///local/path/to/vortex.git@stable        # Will auto-discover the latest stable tag from local repo.
# file:///local/path/to/vortex.git@1.2.3         # Will use specific release from local repo.
# file:///local/path/to/vortex.git@abcd123       # Will use specific commit from local repo.
# /local/path/to/vortex.git                      # Will auto-discover the latest stable tag from local repo.
# /local/path/to/vortex.git@stable               # Will auto-discover the latest stable tag from local repo.
# /local/path/to/vortex.git@1.2.3                # Will use specific release from local repo.
# /local/path/to/vortex.git@abcd123              # Will use specific commit from local repo.
VORTEX_INSTALL_TEMPLATE_REPO="${VORTEX_INSTALL_TEMPLATE_REPO:-${1:-https://github.com/drevops/vortex.git@stable}}"

# The URL of the installer script.
VORTEX_INSTALLER_URL="${VORTEX_INSTALLER_URL:-https://www.vortextemplate.com/install}"

# Cache busting parameter for the installer URL.
VORTEX_INSTALLER_URL_CACHE_BUST="${VORTEX_INSTALLER_URL_CACHE_BUST:-"$(date +%s)"}"

# The path to the installer script.
# If set, this will override the VORTEX_INSTALLER_URL.
VORTEX_INSTALLER_PATH="${VORTEX_INSTALLER_PATH:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

# Pre-flight checks.
for cmd in php curl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

if [ -n "${VORTEX_INSTALLER_PATH}" ]; then
  note "Using installer script from local path: ${VORTEX_INSTALLER_PATH}"
  if [ ! -f "${VORTEX_INSTALLER_PATH}" ]; then
    fail "Installer script not found at ${VORTEX_INSTALLER_PATH}"
    exit 1
  fi
else
  note "Using installer script from URL: ${VORTEX_INSTALLER_URL}"
  VORTEX_INSTALLER_PATH="installer.php"
  note "Downloading installer to ${VORTEX_INSTALLER_PATH}"
  if ! curl -fsSL "${VORTEX_INSTALLER_URL}?${VORTEX_INSTALLER_URL_CACHE_BUST}" -o "${VORTEX_INSTALLER_PATH}"; then
    fail "Failed to download installer from ${VORTEX_INSTALLER_URL}"
    exit 1
  fi
fi

php "${VORTEX_INSTALLER_PATH}" --no-interaction --uri="${VORTEX_INSTALL_TEMPLATE_REPO}"
