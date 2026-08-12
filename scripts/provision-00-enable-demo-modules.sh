#!/usr/bin/env bash
##
# Enable the modules and the content model that the demo site is built from.
#
# This script is called during site provisioning via the provision script.
#
# Replace the operations below with your own once the site stops relying on the
# demo content.
#
# shellcheck disable=SC2086

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     < %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; exit "${2:-1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started demo modules operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

if ! echo "${environment}" | grep -qxF -e local -e ci -e dev -e stage; then
  note "Skipped demo modules operations in production environment."
  exit 0
fi

#;< CONTENT_MODEL
# Site modules attach behaviour to the 'page' content type, so it must exist
# before those modules are installed and their deploy hooks run.
task "Creating the content model."
# Guard against environments where the Drupal CLI is not available.
if [ -x ./vendor/bin/dr ]; then
  ./vendor/bin/dr recipe "$(pwd)/recipes/page" --no-interaction
  pass "Created the content model."
else
  note "Skipped creating the content model: Drupal CLI is not available."
fi
#;> CONTENT_MODEL

task "Setting site name."
drush php:eval "\Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
pass "Set site name."

# Use the core Navigation module as the administration interface and remove
# the classic Toolbar so the two admin systems never run at once. Uninstall
# only when Toolbar is actually enabled (it is absent on re-provision or a
# navigation-based database); a genuine uninstall failure must still abort.
task "Setting up the administration navigation."
drush pm:install navigation
if [ "$(drush php:eval "print \Drupal::moduleHandler()->moduleExists('toolbar');")" = "1" ]; then
  drush pm:uninstall toolbar
fi
pass "Set up the administration navigation."

#;< MODULES
task "Installing contrib modules."
drush pm:install coffee config_split config_update media environment_indicator navigation_extra_tools pathauto redirect reroute_email robotstxt shield stage_file_proxy xmlsitemap
pass "Installed contrib modules."
#;> MODULES

#;< SERVICE_REDIS
task "Installing Redis module."
drush pm:install redis || true
pass "Installed Redis module."
#;> SERVICE_REDIS

#;< SERVICE_CLAMAV
task "Installing and configuring ClamAV."
drush pm:install clamav
drush config:set clamav.settings mode_daemon_tcpip.hostname clamav
pass "Installed and configured ClamAV."
#;> SERVICE_CLAMAV

#;< SERVICE_SOLR
task "Installing Solr search modules."
drush pm:install search_api search_api_solr
pass "Installed Solr search modules."
#;> SERVICE_SOLR

# Enable custom site module and run its deployment hooks.
#
# Note that deployment hooks for already enabled modules have run in the
# parent "provision.sh" script.
task "Installing custom site modules."
#;< CUSTOM_MODULE_BASE
drush pm:install ys_base
#;> CUSTOM_MODULE_BASE

#;< CUSTOM_MODULE_SEARCH
drush pm:install ys_search
#;> CUSTOM_MODULE_SEARCH

#;< CUSTOM_MODULE_DEMO
drush pm:install ys_demo
#;> CUSTOM_MODULE_DEMO
pass "Installed custom site modules."

task "Running deployment hooks."
drush deploy:hook
pass "Ran deployment hooks."

info "Finished demo modules operations."
