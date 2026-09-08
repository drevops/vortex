#!/usr/bin/env bash
##
# Acquia Cloud hook: Provision site.
#

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

site="${1:?Missing required site name.}"
target_env="${2:?Missing required target environment name.}"

[ "${VORTEX_PROVISION_ACQUIA_SKIP:-}" = "1" ] && echo "Skipping provisioning of the site in Acquia environment." && exit 0

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

# Allow custom PHP runtime configuration for Drush CLI commands.
PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$(pwd)/drush/php-ini"
export PHP_INI_SCAN_DIR

export VORTEX_UNBLOCK_ADMIN="${VORTEX_UNBLOCK_ADMIN:-0}"

./vendor/bin/vortex-provision

popd >/dev/null || exit 1
