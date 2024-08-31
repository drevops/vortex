#!/usr/bin/env bash
##
# Provision site by importing it from the database dump or installing from
# profile and running additional steps.
#
# This script has excessive verbose output to make it easy to debug site
# provisions and deployments.
#
# shellcheck disable=SC1091,SC2086,SC2002,SC2235,SC1090,SC2012,SC2015

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Flag to skip site provisioning.
VORTEX_PROVISION_SKIP="${VORTEX_PROVISION_SKIP:-}"

# Provision a site from profile instead of database file dump.
VORTEX_PROVISION_USE_PROFILE="${VORTEX_PROVISION_USE_PROFILE:-0}"

# Flag to always overwrite existing database. Usually set to 0 in deployed
# environments.
VORTEX_PROVISION_OVERRIDE_DB="${VORTEX_PROVISION_OVERRIDE_DB:-0}"

# Skip database sanitization.
VORTEX_PROVISION_SANITIZE_DB_SKIP="${VORTEX_PROVISION_SANITIZE_DB_SKIP:-0}"

# Put the site into a maintenance mode during site provisioning phase.
VORTEX_PROVISION_USE_MAINTENANCE_MODE="${VORTEX_PROVISION_USE_MAINTENANCE_MODE:-1}"

# Flag to skip running of operations after site provision is complete.
# Useful to only import the database from file (or install from profile) and not
# perform any additional operations. For example, when need to capture database
# state before any updates ran (for example, DB caching in CI).
VORTEX_PROVISION_POST_OPERATIONS_SKIP="${VORTEX_PROVISION_POST_OPERATIONS_SKIP:-0}"

# Current environment name discovered during site provisioning.
VORTEX_PROVISION_ENVIRONMENT="${VORTEX_PROVISION_ENVIRONMENT:-}"

# Name of the webroot directory with Drupal codebase.
VORTEX_WEBROOT="${VORTEX_WEBROOT:-web}"

# Drupal site name.
DRUPAL_SITE_NAME="${DRUPAL_SITE_NAME:-${VORTEX_PROJECT:-Example site}}"

# Drupal site email.
DRUPAL_SITE_EMAIL="${DRUPAL_SITE_EMAIL:-webmaster@example.com}"

# Profile machine name.
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

# Path to configuration directory relative to the project root.
# Auto-discovered from site's `settings.php` file if not set.
DRUPAL_CONFIG_PATH="${DRUPAL_CONFIG_PATH:-}"

# Directory with database dump file.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Database dump file name.
VORTEX_DB_FILE="${VORTEX_DB_FILE:-db.sql}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

drush() { ./vendor/bin/drush -y "$@"; }
yesno() { [ "${1}" = "1" ] && echo "Yes" || echo "No"; }

# ------------------------------------------------------------------------------

info "Started site provisioning."

[ "${VORTEX_PROVISION_SKIP}" = "1" ] && pass "Skipped site provisioning as VORTEX_PROVISION_SKIP is set to 1." && exit 0

## Convert DB dir starting with './' to a full path.
[ "${VORTEX_DB_DIR#./}" != "${VORTEX_DB_DIR}" ] && VORTEX_DB_DIR="$(pwd)${VORTEX_DB_DIR#.}"

drush_version="$(drush --version | cut -d' ' -f4)"
drupal_version="$(drush status --field=drupal-version)"
site_is_installed="$(drush status --fields=bootstrap | grep -q "Successful" && echo "1" || echo "0")"

# Discover the configuration directory path if not set.
if [ -z "${DRUPAL_CONFIG_PATH}" ]; then
  DRUPAL_CONFIG_PATH="$(drush php:eval 'print realpath(\Drupal\Core\Site\Settings::get("config_sync_directory"));')"
  [ ! -d "${DRUPAL_CONFIG_PATH}" ] && fail "Config directory \"${DRUPAL_CONFIG_PATH:-<empty>}\" does not exist." && exit 1
fi

site_has_config="$(test "$(ls -1 ${DRUPAL_CONFIG_PATH}/*.yml 2>/dev/null | wc -l | tr -d ' ')" -gt 0 && echo "1" || echo "0")"

################################################################################
# Print provisioning information.
################################################################################
echo
note "Drupal core version            : ${drupal_version}"
note "Drush version                  : ${drush_version}"
echo
note "Webroot path                   : $(pwd)/${VORTEX_WEBROOT}"
note "Public files path              : ${DRUPAL_PUBLIC_FILES-<empty>}"
note "Private files path             : ${DRUPAL_PRIVATE_FILES-<empty>}"
note "Temporary files path           : ${DRUPAL_TEMPORARY_FILES-<empty>}"
note "Config files path              : ${DRUPAL_CONFIG_PATH}"
note "DB dump file path              : ${VORTEX_DB_DIR}/${VORTEX_DB_FILE} ($([ -f "${VORTEX_DB_DIR}/${VORTEX_DB_FILE}" ] && echo "present" || echo "absent"))"
if [ -n "${VORTEX_DB_IMAGE:-}" ]; then
  note "DB dump container image        : ${VORTEX_DB_IMAGE}"
fi
echo
note "Profile                        : ${DRUPAL_PROFILE}"
note "Configuration files present    : $(yesno "${site_has_config}")"
note "Existing site found            : $(yesno "${site_is_installed}")"
echo
note "Install from profile           : $(yesno "${VORTEX_PROVISION_USE_PROFILE}")"
note "Overwrite existing DB          : $(yesno "${VORTEX_PROVISION_OVERRIDE_DB}")"
note "Skip DB sanitization           : $(yesno "${VORTEX_PROVISION_SANITIZE_DB_SKIP}")"
note "Skip post-provision operations : $(yesno "${VORTEX_PROVISION_POST_OPERATIONS_SKIP}")"
note "Use maintenance mode           : $(yesno "${VORTEX_PROVISION_USE_MAINTENANCE_MODE}")"
echo
################################################################################

#
# Provision site by importing the database from the dump file.
#
provision_from_db() {
  if [ ! -f "${VORTEX_DB_DIR}/${VORTEX_DB_FILE}" ]; then
    echo
    fail "Unable to import database from file."
    note "Dump file ${VORTEX_DB_DIR}/${VORTEX_DB_FILE} does not exist."
    note "Site content was not changed."
    exit 1
  fi

  drush sql:drop

  drush sql:cli <"${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"

  pass "Imported database from the dump file."
}

#
# Provision site from the profile.
#
provision_from_profile() {
  local opts=()

  opts+=(
    "${DRUPAL_PROFILE}"
    --site-name="${DRUPAL_SITE_NAME}"
    --site-mail="${DRUPAL_SITE_EMAIL}"
    --account-name=admin
    install_configure_form.enable_update_status_module=NULL
    install_configure_form.enable_update_status_emails=NULL
  )

  [ -n "${DRUPAL_ADMIN_EMAIL:-}" ] && opts+=(--account-mail="${DRUPAL_ADMIN_EMAIL:-}")

  [ "${site_has_config}" = "1" ] && opts+=(--existing-config)

  # Database may exist in non-bootstrappable state - truncate it.
  drush sql:drop || true

  drush site:install "${opts[@]}"

  pass "Installed a site from the profile."
}

# Provision site from DB dump or profile.
#
# The code block below has explicit if-else conditions and verbose output to
# ensure that this significant operation is executed correctly and has
# sufficient output for debugging.
if [ "${VORTEX_PROVISION_USE_PROFILE}" != "1" ]; then
  info "Provisioning site from the database dump file."
  note "Dump file path: ${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"

  if [ "${site_is_installed}" = "1" ]; then
    note "Existing site was found when provisioning from the database dump file."

    if [ "${VORTEX_PROVISION_OVERRIDE_DB}" = "1" ]; then
      note "Existing site content will be removed and fresh content will be imported from the database dump file."
      provision_from_db
    else
      note "Site content will be preserved."
      note "Sanitization will be skipped for an existing database."
      export VORTEX_PROVISION_SANITIZE_DB_SKIP=1
    fi
  else
    note "Existing site was not found when installing from the database dump file."
    note "Fresh site content will be imported from the database dump file."
    provision_from_db
    # Let the downstream scripts know that the database was imported.
    export VORTEX_PROVISION_OVERRIDE_DB=1
  fi
else
  info "Provisioning site from the profile."
  note "Profile: ${DRUPAL_PROFILE}."

  if [ "${site_is_installed}" = "1" ]; then
    note "Existing site was found when provisioning from the profile."

    if [ "${VORTEX_PROVISION_OVERRIDE_DB}" = "1" ]; then
      note "Existing site content will be removed and new content will be created from the profile."
      provision_from_profile
      # Let the downstream scripts know that the database was imported.
      export VORTEX_PROVISION_OVERRIDE_DB=1
    else
      note "Site content will be preserved."
      note "Sanitization will be skipped for an existing database."
      export VORTEX_PROVISION_SANITIZE_DB_SKIP=1
    fi
  else
    note "Existing site was not found when provisioning from the profile."
    note "Fresh site content will be created from the profile."
    provision_from_profile
    export VORTEX_PROVISION_OVERRIDE_DB=1
  fi
fi

echo

if [ "${VORTEX_PROVISION_POST_OPERATIONS_SKIP}" = "1" ]; then
  info "Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."
  echo
  info "Finished site provisioning."
  exit 0
fi

if [ "${VORTEX_PROVISION_USE_MAINTENANCE_MODE}" = "1" ]; then
  info "Enabling maintenance mode."
  drush maint:set 1
  pass "Enabled maintenance mode."
  echo
fi

# Get the current environment and export it for the downstream scripts.
VORTEX_PROVISION_ENVIRONMENT="$(drush php:eval "print \Drupal\core\Site\Settings::get('environment');")"
info "Current Drupal environment: ${VORTEX_PROVISION_ENVIRONMENT}"
echo
export VORTEX_PROVISION_ENVIRONMENT

# Use 'drush deploy' if configuration files are present or use standalone commands otherwise.
if [ "${site_has_config}" = "1" ]; then
  if [ -f "${DRUPAL_CONFIG_PATH}/system.site.yml" ]; then
    config_uuid="$(cat "${DRUPAL_CONFIG_PATH}/system.site.yml" | grep uuid | tail -c +7 | head -c 36)"
    drush config-set system.site uuid "${config_uuid}"
    pass "Updated site UUID from the configuration with ${config_uuid}."
    echo
  fi

  info "Running deployment operations via 'drush deploy'."
  drush deploy
  pass "Completed deployment operations via 'drush deploy'."
  echo

  # Import config_split configuration if the module is installed.
  # Drush deploy does not import config_split configuration on the first run.
  # @see https://github.com/drush-ops/drush/issues/2449
  # @see https://www.drupal.org/project/drupal/issues/3241439
  if drush pm:list --status=enabled | grep -q config_split; then
    info "Importing config_split configuration."
    drush config:import
    pass "Completed config_split configuration import."
    echo
  fi
else
  info "Running database updates."
  drush updatedb --no-cache-clear
  pass "Completed running database updates."
  echo

  info "Rebuilding cache."
  drush cache:rebuild
  pass "Cache was rebuilt."
  echo

  info "Running deployment operations via 'drush deploy:hook'."
  drush deploy:hook
  pass "Completed deployment operations via 'drush deploy:hook'."
  echo
fi

# Sanitize database.
if [ "${VORTEX_PROVISION_SANITIZE_DB_SKIP}" != "1" ]; then
  ./scripts/vortex/provision-sanitize-db.sh
else
  info "Skipped database sanitization."
  echo
fi

# Run custom provision scripts.
# The files should be located in "./scripts/custom/" directory
# and must have "provision-" prefix and ".sh" extension.
if [ -d "./scripts/custom" ]; then
  for file in ./scripts/custom/provision-*.sh; do
    if [ -f "${file}" ]; then
      info "Running custom post-install script '${file}'."
      echo
      . "${file}"
      echo
      pass "Completed running of custom post-install script '${file}'."
      echo
    fi
  done
  unset file
fi

if [ "${VORTEX_PROVISION_USE_MAINTENANCE_MODE}" = "1" ]; then
  info "Disabling maintenance mode."
  drush maint:set 0
  pass "Disabled maintenance mode."
  echo
fi

info "Finished site provisioning."
