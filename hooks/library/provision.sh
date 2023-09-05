#!/usr/bin/env bash
##
# Acquia Cloud hook: Provision site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

export DREVOPS_APP="/var/www/html/${site}.${target_env}"

pushd "${DREVOPS_APP}" >/dev/null || exit 1

# Do not unblock admin account.
export DREVOPS_DRUPAL_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_UNBLOCK_ADMIN:-0}"

./scripts/drevops/provision.sh

popd >/dev/null || exit 1
