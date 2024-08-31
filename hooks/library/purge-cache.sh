#!/usr/bin/env bash
#!/usr/bin/env bash
##
# Acquia Cloud hook: Purge edge cache in an environment.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -e
[ -n "${VORTEX_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

[ "${VORTEX_PURGE_CACHE_ACQUIA_SKIP}" = "1" ] && echo "Skipping purging of cache in Acquia environment." && exit 0

export VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY?not set}"
export VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET?not set}"
export VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-${site}}"
export VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV="${VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV:-${target_env}}"
export VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE:-"/var/www/html/${site}.${target_env}/hooks/library/domains.txt"}"

./scripts/vortex/task-purge-cache-acquia.sh

popd >/dev/null || exit 1
