#!/usr/bin/env bats
##
# Unit tests for drupal-install-site.sh
#
#shellcheck disable=SC2030,SC2031,SC2034

load _helper.bash

assert_drupal_install_site_info() {
  local webroot="${8:-web}"

  format_yes_no() {
    [ "${1}" == "1" ] && echo "Yes" || echo "No"
  }

  assert_output_contains "Started site installation."
  assert_output_contains "App dir                      : ${LOCAL_REPO_DIR}"
  assert_output_contains "Webroot dir                  : ${webroot}"
  assert_output_contains "Site name                    : Example site"
  assert_output_contains "Site email                   : webmaster@example.com"
  assert_output_contains "Profile                      : standard"
  assert_output_contains "Private files directory      : ${LOCAL_REPO_DIR}/${webroot}/sites/default/files/private"
  assert_output_contains "Config path                  : ${LOCAL_REPO_DIR}/config/default"
  assert_output_contains "DB dump file path            : ${LOCAL_REPO_DIR}/.data/db.sql"

  assert_output_contains "Drush version                : mocked_drush_version"
  assert_output_contains "Drupal core version          : mocked_core_version"

  assert_output_contains "Install from profile         : $(format_yes_no "${1:-0}")"
  assert_output_contains "Overwrite existing DB        : $(format_yes_no "${2:-0}")"
  assert_output_contains "Skip sanitization            : $(format_yes_no "${3:-0}")"
  assert_output_contains "Use maintenance mode         : $(format_yes_no "${4:-1}")"
  assert_output_contains "Skip post-install operations : $(format_yes_no "${5:-0}")"
  assert_output_contains "Configuration files present  : $(format_yes_no "${6:-0}")"
  assert_output_contains "Existing site found          : $(format_yes_no "${7:-0}")"
}

@test "Site install: DB; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "Installing site from the database dump file."
    "Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "- Existing site was found when installing from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "Existing site was not found when installing from the database dump file."
    "Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Installing site from the profile."
    "- Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "@drush -y sql:query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "Fresh database detected. Performing additional operations."
    "- Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 0 0 0 1 0 0 0

  popd >/dev/null || exit 1
}

@test "Site install: DB; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush" "drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "Installing site from the database dump file."
    "Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "Existing site was found when installing from the database dump file."
    "Site content will be preserved."
    "Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "- Installing site from the profile."
    "- Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "- Fresh database detected. Performing additional operations."
    "Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 0 0 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Site install: DB; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=1

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "Installing site from the database dump file."
    "Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "Existing site was found when installing from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Installing site from the profile."
    "- Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "@drush -y sql:query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "Fresh database detected. Performing additional operations."
    "- Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 0 1 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Site install: DB; no site, configs" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  mocked_uuid="c9360453-e1ea-4292-b074-ea375f97d72b"
  echo "uuid: ${mocked_uuid}" >"${LOCAL_REPO_DIR}/config/default/system.site.yml"
  echo "name: 'SUT'" >>"${LOCAL_REPO_DIR}/config/default/system.site.yml"

  create_global_command_wrapper "vendor/bin/drush"

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "Installing site from the database dump file."
    "Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "- Existing site was found when installing from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "Existing site was not found when installing from the database dump file."
    "Fresh site content will be imported from the database dump file."
    "@drush -y sql:drop"
    "@drush -y sql:cli"
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "Imported database from the dump file."
    # Profile.
    "- Installing site from the profile."
    "- Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "@drush -y sql:query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "Fresh database detected. Performing additional operations."
    "- Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 0 0 0 1 0 1 0

  popd >/dev/null || exit 1
}

@test "Site install: profile; no site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # fail"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "- Installing site from the database dump file."
    "- Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "- Existing site was found when installing from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Installing site from the profile."
    "- Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "@drush -y sql:drop"
    "@drush -y site:install standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"
    "Installed a site from the profile."
    "Existing site was not found when installing from the profile."
    "Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "@drush -y sql:query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "Fresh database detected. Performing additional operations."
    "- Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 1 0 0 1 0 0 0

  popd >/dev/null || exit 1
}

@test "Site install: profile; existing site" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "- Installing site from the database dump file."
    "- Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "- Existing site was found when installing from the database dump file."
    "Site content will be preserved."
    "Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Installing site from the profile."
    "Existing site was found when installing from the profile."
    "- Existing site content will be removed and new content will be created from the profile."
    "- Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "- Fresh database detected. Performing additional operations."
    "Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 1 0 0 1 0 0 1

  popd >/dev/null || exit 1
}

@test "Site install: profile; existing site; overwrite" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Remove .env file to test in isolation.
  rm ./.env && touch ./.env

  export DREVOPS_APP="${LOCAL_REPO_DIR}"
  export DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="MOCK_DB_SANITIZE_PASSWORD"
  export CI=1

  mkdir "${LOCAL_REPO_DIR}/.data"
  touch "${LOCAL_REPO_DIR}/.data/db.sql"

  create_global_command_wrapper "vendor/bin/drush"

  export DREVOPS_DRUPAL_INSTALL_FROM_PROFILE=1
  export DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB=1

  declare -a STEPS=(
    # Drush status calls.
    "@drush -y --version # Drush Commandline Tool mocked_drush_version"
    "@drush -y status --field=drupal-version # mocked_core_version"
    "@drush -y status --fields=bootstrap # Successful"

    # Private files directory.
    "Creating private files directory."
    "Created private files directory."

    # Site installation information.
    "- Installing site from the database dump file."
    "- Dump file path: ${LOCAL_REPO_DIR}/.data/db.sql"
    "- Existing site was found when installing from the database dump file."
    "- Site content will be preserved."
    "- Sanitization will be skipped for an existing database."
    "- Existing site content will be removed and fresh content will be imported from the database dump file."
    "- Existing site was not found when installing from the database dump file."
    "- Fresh site content will be imported from the database dump file."
    "- Unable to import database from file."
    "- Dump file ${LOCAL_REPO_DIR}/.data/db.sql does not exist."
    "- Site content was not changed."
    "- Imported database from the dump file."
    # Profile.
    "Installing site from the profile."
    "Existing site was found when installing from the profile."
    "Existing site content will be removed and new content will be created from the profile."
    "@drush -y sql:drop"
    "@drush -y site:install standard --site-name=Example site --site-mail=webmaster@example.com --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL"
    "Installed a site from the profile."
    "- Existing site was not found when installing from the profile."
    "- Fresh site content will be created from the profile."

    # Post-install operations.
    "- Skipped running of post-install operations as DREVOPS_DRUPAL_INSTALL_OPERATIONS_SKIP is set to 1."

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
    "@drush -y sql:query --file=${LOCAL_REPO_DIR}/scripts/sanitize.sql"
    "Applied custom sanitization commands from file"
    "@drush -y sql:query UPDATE \`users_field_data\` SET mail = '', name = '' WHERE uid = '0';"
    "@drush -y sql:query UPDATE \`users_field_data\` SET name = '' WHERE uid = '0';"
    "Reset user 0 username and email."
    "- Updated user 1 email."
    "- Skipped database sanitization."

    # Custom post-install script.
    "Running custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."
    "@drush php:eval \Drupal::service('config.factory')->getEditable('system.site')->set('name', 'YOURSITE')->save();"
    "@drush -y pm:install ys_core"
    "@drush -y deploy:hook"
    "Executing example operations in non-production environment."
    # Assert that DREVOPS_DRUPAL_INSTALL_OVERRIDE_EXISTING_DB is correctly passed to the script.
    "Fresh database detected. Performing additional operations."
    "- Existing database detected. Skipping additional operations."
    "Completed running of custom post-install script '${LOCAL_REPO_DIR}/scripts/custom/drupal-install-site-10-example-operations.sh'."

    # Disabling maintenance mode.
    "Disabling maintenance mode."
    "@drush -y maint:set 0"
    "Disabled maintenance mode."

    # One-time login link.
    "@drush pm:list --status=enabled # none"
    "@drush sql:query SELECT name FROM \`users_field_data\` WHERE \`uid\` = '1'; # admin"
    "@drush -- user:unblock admin"
    "@drush user:login # MOCK_ONE_TIME_LINK"
    "MOCK_ONE_TIME_LINK"

    # Installation completion.
    "Finished site installation."
  )

  mocks="$(run_steps "setup")"

  # export DREVOPS_DEBUG=1
  run ./scripts/drevops/drupal-install-site.sh
  assert_success

  run_steps "assert" "${mocks[@]}"

  assert_drupal_install_site_info 1 1 0 1 0 0 1

  popd >/dev/null || exit 1
}
