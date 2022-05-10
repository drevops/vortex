#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

[ "${DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP}" = "1" ] && echo "Skipping install site." && exit

export DREVOPS_APP="/var/www/html/${site}.${target_env}"

# Override Drupal config label.
export DREVOPS_DRUPAL_CONFIG_LABEL=vcs

# Do not unblock admin account.
export DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN="${DREVOPS_DRUPAL_LOGIN_UNBLOCK_ADMIN:-0}"

"/var/www/html/${site}.${target_env}/drevops/drupal-install-site.sh"
