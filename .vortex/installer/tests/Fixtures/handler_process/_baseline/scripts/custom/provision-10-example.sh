#!/usr/bin/env bash
##
# Example of the custom per-project command that will run after website is installed.
#
# Clone this file and modify it to your needs or simply remove it.
#
# For ordering multiple commands, use a two-digit suffix for clarity and consistency.
# This approach ensures a clear sequence and avoids potential ordering issues.
#
# Example:
# - provision-10-example.sh
# - provision-20-example.sh
# - provision-30-example.sh
#
# shellcheck disable=SC2086

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { _TASK_START=$(date +%s); [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { _d=""; [ -n "${_TASK_START:-}" ] && _d=" ($(($(date +%s) - _TASK_START))s)" && unset _TASK_START; [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s%s\033[0m\n" "${1}" "${_d}" || printf "[ OK ] %s%s\n" "${1}" "${_d}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

info "Started example operations."

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

# Perform operations based on the current environment.
if echo "${environment}" | grep -q -e dev -e stage -e ci -e local; then
  note "Running example operations in non-production environment."

  task "Setting site name."
  drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'star wars')->save();"
  pass "Set site name."

  task "Installing contrib modules."
  drush pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
  pass "Installed contrib modules."

  task "Installing Redis module."
  drush pm:install redis || true
  pass "Installed Redis module."

  task "Installing and configuring ClamAV."
  drush pm:install clamav
  drush config-set clamav.settings mode_daemon_tcpip.hostname clamav
  pass "Installed and configured ClamAV."

  task "Installing Solr search modules."
  drush pm:install search_api search_api_solr
  pass "Installed Solr search modules."

  # Enable custom site module and run its deployment hooks.
  #
  # Note that deployment hooks for already enabled modules have run in the
  # parent "provision.sh" script.
  task "Installing custom site modules."
  drush pm:install sw_base

  drush pm:install sw_search

  drush pm:install sw_demo
  pass "Installed custom site modules."

  task "Running deployment hooks."
  drush deploy:hook
  pass "Ran deployment hooks."

  # Conditionally perform an action if this is a "fresh" database.
  if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
    note "Fresh database detected. Performing additional example operations."
  else
    note "Existing database detected. Performing additional example operations."
  fi
else
  note "Skipping example operations in production environment."
fi

info "Finished example operations."
