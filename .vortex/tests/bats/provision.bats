#!/usr/bin/env bats
##
# Unit tests for provision.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load _helper.bash

export DRUPAL_PUBLIC_FILES="/app/web/sites/default/files"
export DRUPAL_PRIVATE_FILES="/app/web/sites/default/files/private"
export DRUPAL_TEMPORARY_FILES="/tmp"

assert_provision_info() {
  local webroot="${8:-web}"

  format_yes_no() {
    [ "${1}" == "1" ] && echo "Yes" || echo "No"
  }

  assert_output_contains "Drupal core version            : mocked_core_version"
  assert_output_contains "Drush version                  : mocked_drush_version"

  assert_output_contains "Started site provisioning."
  assert_output_contains "Webroot path                   : $(pwd)/${webroot}"
  assert_output_contains "Public files path              : /app/${webroot}/sites/default/files"
  assert_output_contains "Private files path             : /app/${webroot}/sites/default/files/private"
  assert_output_contains "Temporary files path           : /tmp"
  assert_output_contains "Config files path              : $(pwd)/config/default"
  assert_output_contains "DB dump file path              : $(pwd)/.data/db.sql"

  assert_output_contains "Profile                        : standard"
  assert_output_contains "Configuration files present    : $(format_yes_no "${6:-0}")"
  assert_output_contains "Existing site found            : $(format_yes_no "${7:-0}")"

  assert_output_contains "Install from profile           : $(format_yes_no "${1:-0}")"
  assert_output_contains "Overwrite existing DB          : $(format_yes_no "${2:-0}")"
  assert_output_contains "Skip DB sanitization           : $(format_yes_no "${3:-0}")"
  assert_output_contains "Skip post-provision operations : $(format_yes_no "${5:-0}")"
  assert_output_contains "Use maintenance mode           : $(format_yes_no "${4:-1}")"
}

@test "Provision: DB; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "Provisioning site from the database dump file."
    "Dump file path: $(pwd)/.data/db.sql"
    "- Existing site was found when provisioning from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "Existing site was not found when installing from the database dump file."
    "Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Provisioning site from the profile."
    "- Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "Sanitizing database."
    "@drush -y sql:sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost"
    "Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "@drush -y sql:query --file=../scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "Fresh database detected. Performing additional example operations."
    "- Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 0 0 0 1 0 0 0

  popd >/dev/null || exit 1
}

@test "Provision: DB; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush" "drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "Provisioning site from the database dump file."
    "Dump file path: $(pwd)/.data/db.sql"
    "Existing site was found when provisioning from the database dump file."
    "Site content will be preserved."
    "Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "- Provisioning site from the profile."
    "- Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "- Sanitizing database."
    "- Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "- Applied custom sanitization commands from file"
    "- Reset user 0 username and email."
    "- Updated user 1 email."
    "Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "- Fresh database detected. Performing additional example operations."
    "Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 0 0 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Provision: DB; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  export VORTEX_PROVISION_OVERRIDE_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "Provisioning site from the database dump file."
    "Dump file path: $(pwd)/.data/db.sql"
    "Existing site was found when provisioning from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Provisioning site from the profile."
    "- Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "Sanitizing database."
    "@drush -y sql:sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost"
    "Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "@drush -y sql:query --file=../scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "Fresh database detected. Performing additional example operations."
    "- Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 0 1 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Provision: DB; no site, configs" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  mocked_uuid="c9360453-e1ea-4292-b074-ea375f97d72b"
  echo "uuid: ${mocked_uuid}" >"./config/default/system.site.yml"
  echo "name: 'SUT'" >>"./config/default/system.site.yml"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "Provisioning site from the database dump file."
    "Dump file path: $(pwd)/.data/db.sql"
    "- Existing site was found when provisioning from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "Existing site was not found when installing from the database dump file."
    "Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Provisioning site from the profile."
    "- Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "@drush -y config-set system.site uuid ${mocked_uuid}"
    "Updated site UUID from the configuration with ${mocked_uuid}"
    "Running deployment operations via 'drush deploy'."
    "@drush -y deploy"
    "@drush -y pm:list --status=enabled # config_split"
    "Importing config_split configuration."
    "@drush -y config:import"
    "Completed config_split configuration import."

    # Database updates.
    "- Running database updates."
    "- Completed running database updates."

    # Cache rebuild.
    "- Rebuilding cache."
    "- Cache was rebuilt."

    # Post configuration import updates.
    "- Running deployment operations via 'drush deploy:hook'."
    "- Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "Sanitizing database."
    "@drush -y sql:sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost"
    "Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "@drush -y sql:query --file=../scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "Fresh database detected. Performing additional example operations."
    "- Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 0 0 0 1 0 1 0

  popd >/dev/null || exit 1
}

@test "Provision: profile; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export VORTEX_PROVISION_USE_PROFILE=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "- Provisioning site from the database dump file."
    "- Dump file path: $(pwd)/.data/db.sql"
    "- Existing site was found when provisioning from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Provisioning site from the profile."
    "- Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "@drush -y sql:drop"
    "@drush -y site:install standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"
    "Installed a site from the profile."
    "Existing site was not found when provisioning from the profile."
    "Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "Sanitizing database."
    "@drush -y sql:sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost"
    "Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "@drush -y sql:query --file=../scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "Fresh database detected. Performing additional example operations."
    "- Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 1 0 0 1 0 0 0

  popd >/dev/null || exit 1
}

@test "Provision: profile; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export VORTEX_PROVISION_USE_PROFILE=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "- Provisioning site from the database dump file."
    "- Dump file path: $(pwd)/.data/db.sql"
    "- Existing site was found when provisioning from the database dump file."
    "Site content will be preserved."
    "Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Provisioning site from the profile."
    "Existing site was found when provisioning from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "- Sanitizing database."
    "- Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "- Applied custom sanitization commands from file"
    "- Reset user 0 username and email."
    "- Updated user 1 email."
    "Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "- Fresh database detected. Performing additional example operations."
    "Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 1 0 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Provision: profile; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export VORTEX_PROVISION_SANITIZE_DB_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "./.data"
  touch "./.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export VORTEX_PROVISION_USE_PROFILE=1
  export VORTEX_PROVISION_OVERRIDE_DB=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"
    "@drush -y php:eval print realpath(\Drupal\Core\Site\Settings::get(\"config_sync_directory\")); # $(pwd)/config/default"

    # Site provisioning information.
    "- Provisioning site from the database dump file."
    "- Dump file path: $(pwd)/.data/db.sql"
    "- Existing site was found when provisioning from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file $(pwd)/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Provisioning site from the profile."
    "Existing site was found when provisioning from the profile."
    "Existing site content will be removed and new content will be created from the profile."
    "@drush -y sql:drop"
    "@drush -y site:install standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"
    "Installed a site from the profile."
    "- Existing site was not found when provisioning from the profile."
    "- Fresh site content will be created from the profile."

    # Post-provision operations.
    "- Skipped running of post-provision operations as VORTEX_PROVISION_POST_OPERATIONS_SKIP is set to 1."

    # Maintenance mode.
    "Enabling maintenance mode."
    "@drush -y maint:set 1"
    "Enabled maintenance mode."

    # Drupal environment information.
    "Current Drupal environment: ci"
    "@drush -y php:eval print \Drupal\core\Site\Settings::get('environment'); # ci"

    # Deployment and configuration updates.
    "- Updated site UUID from the configuration with"
    "- Running deployment operations via 'drush deploy'."
    "- Importing config_split configuration."

    # Database updates.
    "Running database updates."
    "@drush -y updatedb --no-cache-clear"
    "Completed running database updates."

    # Cache rebuild.
    "Rebuilding cache."
    "@drush -y cache:rebuild"
    "Cache was rebuilt."

    # Post configuration import updates.
    "Running deployment operations via 'drush deploy:hook'."
    "@drush -y deploy:hook"
    "Completed deployment operations via 'drush deploy:hook'."

    # Database sanitization.
    "Sanitizing database."
    "@drush -y sql:sanitize --sanitize-password=MOCK_DB_SANITIZE_PASSWORD --sanitize-email=user+%uid@localhost"
    "Sanitized database using drush sql:sanitize."
    "- Updated username with user email."
    "@drush -y sql:query --file=../scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script './scripts/custom/provision-10-example.sh'."
    "@drush -y php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install admin_toolbar coffee config_split config_update media environment_indicator pathauto redirect shield stage_file_proxy"
    "@drush -y pm:install redis"
    "@drush -y pm:install clamav"
    "@drush -y config-set clamav.settings mode_daemon_tcpip.hostname clamav"
    "@drush -y pm:install search_api search_api_solr"
    "@drush -y pm:install ys_core ys_search"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that VORTEX_PROVISION_OVERRIDE_DB is correctly passed to the script.
    "Fresh database detected. Performing additional example operations."
    "- Existing database detected. Performing additional example operations."
    "Completed running of custom post-install script './scripts/custom/provision-10-example.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # Installation completion.
    "Finished site provisioning."
  )

  mocks="$(run_steps "setup")"

  # export VORTEX_DEBUG=1
  run ./scripts/vortex/provision.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_provision_info 1 1 0 1 0 0 1

  popd >/dev/null || exit 1
}
