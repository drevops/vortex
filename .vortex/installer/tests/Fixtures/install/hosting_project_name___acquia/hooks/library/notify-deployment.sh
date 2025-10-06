#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notifications.
#

set -e
[ -n "${VORTEX_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"
branch="${3}"
ref="${4:-${branch}}"

# Custom domain name for the environment, including subdomain.
# Examples: "dev.example.com", "test.example.com", "www.example.com"
VORTEX_NOTIFY_ENVIRONMENT_DOMAIN="${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN:-}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

# Set URL to Acquia domain by default.
url="https://${AH_SITE_NAME}.${AH_REALM:-prod}.acquia-sites.com"

# Use custom domain in URL, if provided.
if [ -n "${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN}" ]; then
  url="https://${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN}"
fi

export VORTEX_NOTIFY_PROJECT="${site}"
export VORTEX_NOTIFY_BRANCH="${branch}"
export VORTEX_NOTIFY_REF="${ref}"
export VORTEX_NOTIFY_SHA="${target_env}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="${url}"

./scripts/vortex/notify.sh || true

popd >/dev/null || exit 1
