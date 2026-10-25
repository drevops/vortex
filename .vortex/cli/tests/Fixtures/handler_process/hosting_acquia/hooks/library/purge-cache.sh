#!/usr/bin/env bash
##
# Acquia Cloud hook: Purge edge cache in an environment.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

site="${1:?Missing required site name.}"
target_env="${2:?Missing required target environment name.}"

[ "${VORTEX_PURGE_CACHE_ACQUIA_SKIP:-}" = "1" ] && echo "Skipping purging of cache in Acquia environment." && exit 0

export VORTEX_PLATFORM=acquia
export VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY:?Missing required value.}"
export VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET:?Missing required value.}"
export VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-${site}}"
export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV:-${target_env}}"
export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE:-"/var/www/html/${site}.${target_env}/hooks/library/domains.txt"}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

./vendor/bin/vortex-task purge-cache

popd >/dev/null || exit 1
