#!/usr/bin/env bash
##
# Acquia Cloud hook: Send deployment notifications.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

site="${1:?Missing required site name.}"
target_env="${2:?Missing required target environment name.}"
branch="${3:?Missing required branch name.}"
ref="${4:?Missing required commit reference.}"

[ "${VORTEX_NOTIFY_ACQUIA_SKIP:-}" = "1" ] && echo "Skipping sending of deployment notifications in Acquia environment." && exit 0

# Custom domain name for the environment, including subdomain.
# Examples: "dev.example.com", "test.example.com", "www.example.com"
VORTEX_NOTIFY_ENVIRONMENT_DOMAIN="${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN:-}"

if [ -n "${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN}" ]; then
  url="https://${VORTEX_NOTIFY_ENVIRONMENT_DOMAIN}"
else
  url="https://${AH_SITE_NAME:?Missing required value.}.${AH_REALM:-prod}.acquia-sites.com"
fi

export VORTEX_NOTIFY_PROJECT="${site}"
export VORTEX_NOTIFY_BRANCH="${branch}"
export VORTEX_NOTIFY_SHA="${ref}"
export VORTEX_NOTIFY_PR_NUMBER=""
export VORTEX_NOTIFY_LABEL="${branch}"
export VORTEX_NOTIFY_ENVIRONMENT_URL="${url}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

./vendor/bin/vortex-notify

popd >/dev/null || exit 1
