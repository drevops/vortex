#!/usr/bin/env bash
##
# Acquia Cloud hook: Provision site.
#

set -e
[ -n "${VORTEX_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

# Allow custom PHP runtime configuration for Drush CLI commands.
# The leading colon appends to the default scan directories.
# @see https://github.com/drevops/vortex/issues/1913
PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR:-}:$(pwd)/drush/php-ini"
export PHP_INI_SCAN_DIR

# Do not unblock admin account.
export VORTEX_UNBLOCK_ADMIN="${VORTEX_UNBLOCK_ADMIN:-0}"

./scripts/vortex/provision.sh

popd >/dev/null || exit 1
