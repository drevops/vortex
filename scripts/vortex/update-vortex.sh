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
# https://github.com/drevops/vortex.git         # Will auto-discover the latest stable tag.
# https://github.com/drevops/vortex.git@stable  # Will auto-discover the latest stable tag.
# /local/path/to/vortex
# /local/path/to/vortex@stable
# /local/path/to/vortex@ref
VORTEX_INSTALL_REPO="${VORTEX_INSTALL_REPO:-${1:-}}"

# The URL of the installer script.
VORTEX_INSTALLER_URL="${VORTEX_INSTALLER_URL:-https://vortex.drevops.com/install}"

# ------------------------------------------------------------------------------

export VORTEX_INSTALL_REPO
export VORTEX_INSTALLER_URL

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

curl -L "${VORTEX_INSTALLER_URL}"?"$(date +%s)" >/tmp/install
php /tmp/install --no-interaction
rm /tmp/install >/dev/null
