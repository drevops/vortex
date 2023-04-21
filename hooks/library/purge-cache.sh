#!/usr/bin/env bash
#!/usr/bin/env bash
##
# Acquia Cloud hook: Purge edge cache in an environment.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

[ "${DREVOPS_TASK_PURGE_CACHE_ACQUIA_SKIP}" = "1" ] && echo "Skipping purging of cache in Acquia environment." && exit 0

export DREVOPS_ACQUIA_KEY="${DREVOPS_ACQUIA_KEY?not set}"
export DREVOPS_ACQUIA_SECRET="${DREVOPS_ACQUIA_SECRET?not set}"
export DREVOPS_ACQUIA_APP_NAME="${DREVOPS_ACQUIA_APP_NAME:-${site}}"
export DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_ENV:-${target_env}}"
export DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE="${DREVOPS_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE:-"/var/www/html/${site}.${target_env}/hooks/library/domains.txt"}"

"/var/www/html/${site}.${target_env}/scripts/drevops/task-purge-cache-acquia.sh"
