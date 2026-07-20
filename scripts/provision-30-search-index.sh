#!/usr/bin/env bash
##
# Reset the search index tracker and run search indexing.
#
# This script is called during site provisioning via the provision script.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Skip search indexing.
DRUPAL_SEARCH_INDEX_SKIP="${DRUPAL_SEARCH_INDEX_SKIP:-0}"

# ------------------------------------------------------------------------------

# @formatter:off
info() { printf "   ==> %s\n" "${1}"; }
note() { printf "       %s\n" "${1}"; }
task() { printf "     > %s\n" "${1}"; }
pass() { printf "     < %s\n" "${1}"; }
fail() { printf "     ! %s\n" "${1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }

# ------------------------------------------------------------------------------

info "Started search indexing operations."

environment="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

note "Search indexing skip: ${DRUPAL_SEARCH_INDEX_SKIP}"
echo

if [ "${DRUPAL_SEARCH_INDEX_SKIP}" = "1" ]; then
  info "Skipped search indexing. DRUPAL_SEARCH_INDEX_SKIP is set to 1."
  exit 0
fi

if echo "${environment}" | grep -q -e local -e ci -e dev -e stage; then
  task "Resetting search index tracker."
  drush search-api:reset-tracker
  pass "Reset search index tracker."

  task "Running search indexing."
  drush search-api:index
  pass "Completed search indexing."
else
  note "Skipped search indexing in non-development environment."
fi

info "Finished search indexing operations."
