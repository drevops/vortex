#!/usr/bin/env bash
##
# Acquia Cloud hook: Provision site.
#

set -e
[ -n "${VORTEX_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

# Do not unblock admin account.
export DRUPAL_UNBLOCK_ADMIN="${DRUPAL_UNBLOCK_ADMIN:-0}"

./scripts/vortex/provision.sh

popd >/dev/null || exit 1
