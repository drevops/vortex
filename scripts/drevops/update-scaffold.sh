#!/usr/bin/env bash
##
# Update DrevOps Scaffold.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Re-export variables only from .env to ignore any local overrides in .env.local.
# shellcheck disable=SC1091
set -a && . ./.env && set +a

# Allow providing custom Scaffold commit hash to download the sources from.
DREVOPS_INSTALL_COMMIT="${DREVOPS_INSTALL_COMMIT:-${1:-}}"

# The URL of the installer script.
DREVOPS_INSTALLER_URL="${DREVOPS_INSTALLER_URL:-https://install.drevops.com}"

# ------------------------------------------------------------------------------

export DREVOPS_INSTALLER_URL
export DREVOPS_INSTALL_COMMIT

for cmd in php curl; do command -v ${cmd} >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

curl -L "${DREVOPS_INSTALLER_URL}"?"$(date +%s)" >/tmp/install
php /tmp/install --quiet
rm /tmp/install >/dev/null
