#!/usr/bin/env bash
##
# Generate content from the Generated content module plugins.
#
# This script is called during site provisioning via the provision script.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Skip content generation.
DRUPAL_GENERATED_CONTENT_SKIP="${DRUPAL_GENERATED_CONTENT_SKIP:-0}"

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

info "Started content generation operations."

environment="$(drush php:eval "print \Drupal\Core\Site\Settings::get('environment');")"
note "Environment: ${environment}"

note "Fresh database: ${VORTEX_PROVISION_OVERRIDE_DB:-0}"

note "Content generation skip: ${DRUPAL_GENERATED_CONTENT_SKIP}"
echo

if [ "${DRUPAL_GENERATED_CONTENT_SKIP}" = "1" ]; then
  info "Skipped content generation. DRUPAL_GENERATED_CONTENT_SKIP is set to 1."
  exit 0
fi

if ! echo "${environment}" | grep -qxF -e local -e ci -e dev -e stage; then
  note "Skipped content generation in production environment."
  exit 0
fi

if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" != "1" ]; then
  note "Existing database detected. Skipped content generation to keep the existing content."
  exit 0
fi

# The module is a dependency of the demo module rather than a standalone
# selection, so it can be absent from a site that keeps the package.
if [ "$(drush php:eval "print \Drupal::moduleHandler()->moduleExists('generated_content');")" != "1" ]; then
  note "Skipped content generation: the Generated content module is not enabled."
  exit 0
fi

# The module generates from hook_modules_installed(), which cannot fire for
# modules installed earlier in provisioning, so the repository is called
# directly. A non-empty repository means the imported database already carries
# generated content and a second run would duplicate it.
if [ "$(drush php:eval "print \Drupal\generated_content\GeneratedContentRepository::getInstance()->isEmpty() ? 1 : 0;")" != "1" ]; then
  note "Skipped content generation: generated content already exists."
  exit 0
fi

# Generation is heavier than the rest of provisioning and exceeds the limit set
# in drush/php-ini/drush.ini.
task "Generating content."
drush php:eval "ini_set('memory_limit', '-1'); \Drupal\generated_content\GeneratedContentRepository::getInstance()->createEntities();"
pass "Generated content."

info "Finished content generation operations."
