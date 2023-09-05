#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

export DREVOPS_APP="/var/www/html/${site}.${target_env}"

pushd "${DREVOPS_APP}" >/dev/null || exit 1

[ "${DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP}" = "1" ] && echo "Skipping install site." && exit

# Do not unblock admin account.
export DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN:-0}"

./scripts/drevops/drupal-install-site.sh

popd >/dev/null || exit 1
