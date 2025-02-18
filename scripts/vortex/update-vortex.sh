#!/usr/bin/env bash
##
# Update Vortex.
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
# https://github.com/drevops/vortex.git
# https://github.com/drevops/vortex.git@ref
# /path/to/vortex
# /path/to/vortex@ref
VORTEX_INSTALL_REPO_URI="${VORTEX_INSTALL_REPO_URI:-${1:-}}"

# The URL of the installer script.
VORTEX_INSTALLER_URL="${VORTEX_INSTALLER_URL:-https://vortex.drevops.com/install}"

# ------------------------------------------------------------------------------

export VORTEX_INSTALLER_URL
export VORTEX_INSTALL_COMMIT

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

curl -L "${VORTEX_INSTALLER_URL}"?"$(date +%s)" >/tmp/install
php /tmp/install --quiet
rm /tmp/install >/dev/null
